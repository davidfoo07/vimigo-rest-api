<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;

class ApiController extends Controller {
    // Register API (POST)
    public function register(Request $request) {
        $request->validate([
            "name" => "required",
            "email" => "required|email|unique:users",
            "password" => "required|confirmed",
            "address" => "required",
        ]);

        // Create User
        User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "address" => $request->address,
            "study_course" => $request->study_course
        ]);

        return response()->json([
            "status" => true,
            "message" => "User created successfully"
        ]);
    }

    // Login API (POST)
    public function login(Request $request) {
        // Data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        // Checking User login
        if (Auth::attempt([
            "email" => $request->email,
            "password" => $request->password
        ])) {
            // User exists.
            $user = Auth::user();
            $token = $user->createToken("myToken")->accessToken;
            return response()->json([
                "status" => true,
                "message" => "User logged in successfully",
                "token" => $token 
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "invalid login details"
            ]);
        }
    }

   // Search API (GET)
    public function search(Request $request)
    {
        $query = Student::query(); // Assuming you have a User model to query from
        $rules = [
            'name' => 'sometimes|required|min:1',
            'email' => 'sometimes|required|email',
        ];

        // Create a validator with the request inputs and the rules
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->has('name')) {
            $query->where('name', 'LIKE', "%" . $request->input('name') . "%");
        }

        if ($request->has('email')) {
            $query->where('email', $request->input('email'));
        }

        $student = $query->get(); // Get the results of the query

        if ($student->isEmpty()) {
        return response()->json([
            'message' => 'No users found with the given name or email.'
        ], 404); // Using 404 status code for not found
    }

        return UserResource::collection($student);
    }

    // Import
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $file = $request->file('file');
        
        $extension = $file->getClientOriginalExtension();

        $readerType = null;
        if ($extension === 'xlsx') {
            $readerType = \Maatwebsite\Excel\Excel::XLSX;
        } elseif ($extension === 'csv') {
            $readerType = \Maatwebsite\Excel\Excel::CSV;
        } else {
            return response()->json(['error', 'Unsupported file type.']);
        }

        Student::truncate();
        $import = new StudentsImport;
        Excel::import($import, $file, null, $readerType);

        $duplicates = $import->getDuplicates();
        if (!empty($duplicates)) {
            return response()->json(['Duplicate email addresses found: '  . implode(', ', $duplicates), 'duplicate rows removed']);
        }

        return response()->json(['success', 'Students imported successfully.']);
    }



    // Update
    public function bulkUpdate(Request $request)
    {
        // check if email exists
        if (!$request->has('email')) {
            return response()->json(["Error" => "email parameter is missing."], 404);
        }
        $update = Student::where('email', $request->input('email'))->update($request->all());
        
        if ($update == 0) {
            return response()->json(['Error' => 'Email does not exist'], 400);
        }

        return response()->json(['message' => 'Students updated successfully']);
    }

    // Delete
    public function bulkDelete(Request $request)
    {
        // check if email exists
        if (!$request->has('email')) {
            return response()->json(['Error' => 'Email parameter is missing.'], 400);
        }

        $delete = Student::where('email', $request->input('email'))->delete();

        if ($delete == 0) {
            return response()->json(['Error' => 'Email does not exist'], 400);
        }

        return response()->json(['message' => 'Students deleted successfully']);
    }

    // Pagination
    public function index(Request $request)
    {
        $users = Student::paginate($request->get('per_page', 10));
        return UserResource::collection($users);
    }


    // Logout API (GET
    public function logout() {
        auth()->user()->token()->revoke();

        return response()->json([
            "status" => true,
            "message" => "User logout"
        ]);
    }
}
