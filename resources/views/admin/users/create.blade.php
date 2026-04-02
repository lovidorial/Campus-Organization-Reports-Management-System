<x-app-layout>
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
        ← Back to Members
    </a>
    <h2 class="text-3xl font-bold text-gray-800 mt-3">Create New User</h2>
    <p class="text-sm text-gray-500 mt-1">Add a new member to the system</p>
</div>

<div class="max-w-2xl bg-white rounded-xl shadow-sm border border-gray-200 p-8">
    <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required 
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 @error('name') border-red-500 @enderror"
                   placeholder="John Doe">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 @error('email') border-red-500 @enderror"
                   placeholder="user@example.com">
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Password -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" required
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 @error('password') border-red-500 @enderror"
                   placeholder="Minimum 8 characters">
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password <span class="text-red-500">*</span></label>
            <input type="password" name="password_confirmation" required
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                   placeholder="Confirm password">
        </div>

        <!-- Role -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
            <select name="role" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 @error('role') border-red-500 @enderror">
                <option value="">Select a role</option>
                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            @error('role') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Position -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Position/Role in Organization</label>
            <input type="text" name="position" value="{{ old('position') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g., President, Vice President">
            @error('position') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- College -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">College/Department</label>
            <input type="text" name="college" value="{{ old('college') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g., College of Engineering">
            @error('college') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Term -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Term</label>
            <input type="text" name="term" value="{{ old('term') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g., 1st Term">
            @error('term') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- School Year -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">School Year</label>
            <input type="text" name="school_year" value="{{ old('school_year') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                   placeholder="e.g., 2025-2026">
            @error('school_year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Form Actions -->
        <div class="flex gap-4 pt-6 border-t">
            <button type="submit" class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition">
                <i class="fas fa-plus-circle me-2"></i>Create User
            </button>
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded-lg transition">
                Cancel
            </a>
        </div>
    </form>
</div>
</x-app-layout>
