$u = App\Models\User::find(2);
if ($u) {
try {
$u->role = 'Pilgrim';
$u->save();
echo "SUCCESS: Updated User 2 role to " . $u->role . "\n";
} catch (\Exception $e) {
echo "ERROR: " . $e->getMessage() . "\n";
}
} else {
echo "User 2 not found\n";
}
exit();