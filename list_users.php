$users = App\Models\User::all(['user_id', 'full_name', 'email', 'role']);
if ($users->isEmpty()) {
echo "No users found in database.\n";
} else {
foreach($users as $user){
echo "ID: " . $user->user_id . " | Name: " . $user->full_name . " | Role: " . $user->role . "\n";
}
}
exit();