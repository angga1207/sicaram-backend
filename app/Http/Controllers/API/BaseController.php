<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Instance;
use App\Models\Ref\Satuan;
use App\Models\Ref\Periode;
use App\Models\Ref\Program;
use App\Models\Ref\Kegiatan;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use App\Models\InstanceSubUnit;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Ref\SubKegiatan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    use JsonReturner;

    function listRole(Request $request)
    {
        try {
            $roles = DB::table('roles')
                ->whereNotIn('id', [1, 11])
                ->when($request->search, function ($query, $search) {
                    return $query->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('display_name', 'ilike', '%' . $search . '%');
                })
                ->select(['id', 'name', 'display_name'])
                ->get();
            $datas = [];
            foreach ($roles as $role) {
                $datas[] = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'users_count' => DB::table('users')->where('role_id', $role->id)->count(),
                ];
            }
            return $this->successResponse($datas, 'List of roles');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function createRole(Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required|string',
            ], [], [
                'name' => 'Nama',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            $data = DB::table('roles')
                ->insert([
                    'display_name' => $request->name,
                    'name' => str()->slug($request->name),
                    'guard_name' => 'web',
                ]);
            if (!$data) {
                return $this->errorResponse('Peran Pengguna gagal dibuat');
            }
            return $this->successResponse($data, 'Peran Pengguna berhasil dibuat');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function detailRole($id, Request $request)
    {
        try {
            $data = DB::table('roles')
                ->where('id', $id)
                ->first();
            if (!$data) {
                return $this->errorResponse('Peran Pengguna tidak ditemukan', 404);
            }
            return $this->successResponse($data, 'Peran Pengguna berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function updateRole($id, Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'name' => 'required|string',
            ], [], [
                'name' => 'Nama',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            $data = DB::table('roles')
                ->where('id', $id)
                ->update([
                    'display_name' => $request->name,
                    'name' => str()->slug($request->name),
                    'guard_name' => 'web',
                ]);
            if (!$data) {
                return $this->errorResponse('Peran Pengguna gagal diperbarui');
            }
            return $this->successResponse($data, 'Peran Pengguna berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteRole($id)
    {
        try {
            $data = DB::table('roles')
                ->where('id', $id)
                ->delete();
            if (!$data) {
                return $this->errorResponse('Peran Pengguna gagal dihapus');
            }
            return $this->successResponse($data, 'Peran Pengguna berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function listUser(Request $request)
    {
        try {
            if ($request->_fRole == 'admin') {
                $users = User::search($request->search)
                    ->whereIn('role_id', [2, 3, 4, 5, 10])
                    ->when(auth()->user()->role_id == 3, function ($query) {
                        return $query->whereIn('role_id', [3, 6, 9, 10, 11]);
                    })
                    ->when(auth()->user()->role_id == 4, function ($query) {
                        return $query->whereIn('role_id', [4, 7, 9, 10, 11]);
                    })
                    ->when(auth()->user()->role_id == 5, function ($query) {
                        return $query->whereIn('role_id', [5, 8, 9, 10, 11]);
                    })
                    ->orderBy('role_id')
                    ->orderBy('created_at', 'asc')
                    ->get();
            } elseif ($request->_fRole == 'verifikator') {
                $users = User::search($request->search)
                    ->whereIn('role_id', [6, 7, 8])
                    ->when(auth()->user()->role_id == 3, function ($query) {
                        return $query->whereIn('role_id', [6]);
                    })
                    ->when(auth()->user()->role_id == 4, function ($query) {
                        return $query->whereIn('role_id', [7]);
                    })
                    ->when(auth()->user()->role_id == 5, function ($query) {
                        return $query->whereIn('role_id', [8]);
                    })
                    ->orderBy('role_id')
                    ->orderBy('created_at', 'asc')
                    ->get();
            } elseif ($request->_fRole == 'perangkat_daerah') {
                $instance = $request->instance;
                $users = User::search($request->search)
                    ->whereIn('role_id', [9])
                    ->when($request->instance, function ($query) use ($instance) {
                        return $query->where('instance_id', $instance);
                    })
                    ->get();
            } else {
                $this->validationResponse('Role is required');
            }

            $roles = DB::table('roles')
                ->whereNotIn('id', [1, 11])
                ->when(auth()->user()->role_id == 3, function ($query) {
                    return $query->whereIn('id', [3, 6, 9, 10, 11]);
                })
                ->when(auth()->user()->role_id == 4, function ($query) {
                    return $query->whereIn('id', [4, 7, 9, 10, 11]);
                })
                ->when(auth()->user()->role_id == 5, function ($query) {
                    return $query->whereIn('id', [5, 8, 9, 10, 11]);
                })
                ->select(['id', 'name', 'display_name'])
                ->get();
            $instances = DB::table('instances')
                ->where('deleted_at', null)
                ->get();

            $datas = [];
            foreach ($users as $user) {
                $instanceIds = [];
                if ($user->role_id == 6) {
                    $Ids = DB::table('pivot_user_verificator_instances')
                        ->where('user_id', $user->id)
                        ->get();
                    foreach ($Ids as $id) {
                        $instanceIds[] = DB::table('instances')->where('id', $id->instance_id)->first()->name ?? 'null';
                    }
                }
                $datas[] = [
                    'id' => $user->id,
                    'fullname' => $user->fullname,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'role_name' => DB::table('roles')->where('id', $user->role_id)->first()->display_name ?? null,
                    'instance_id' => $user->instance_id,
                    'instance_name' => DB::table('instances')->where('id', $user->instance_id)->first()->name ?? null,
                    'instance_type' => $user->instance_type,
                    'instance_ids' => $instanceIds ?? [],
                    'photo' => asset($user->photo),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            }

            $returnData = [
                'roles' => $roles,
                'users' => $datas,
                'instances' => $instances,
            ];
            return $this->successResponse($returnData, 'List of users');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' - ' . $e->getLine());
        }
    }

    function createUser(Request $request)
    {
        // return $request->all();
        DB::beginTransaction();
        try {
            if (in_array(auth()->user()->role_id, [6, 7, 8, 10, 11])) {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }
            if (auth()->user()->role_id == 9 && auth()->user()->instance_type != 'kepala') {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }

            $validate = Validator::make($request->all(), [
                'fullname' => 'required|string',
                'firstname' => 'nullable|string',
                'lastname' => 'nullable|string',
                'username' => 'required|alpha_dash|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'role' => 'required|integer|exists:roles,id',
                'instance_id' => 'nullable|integer|exists:instances,id',
                'instance_type' => 'nullable|string',
                'instance_ids' => 'nullable|array',
                'instance_ids.*' => 'nullable',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
                'password' => 'required|string',
                'password_confirmation' => 'required|string|same:password',
            ], [], [
                'fullname' => 'Nama lengkap',
                'firstname' => 'Nama depan',
                'lastname' => 'Nama belakang',
                'username' => 'Username',
                'email' => 'Email',
                'role' => 'Role',
                'instance_id' => 'Instance',
                'instance_type' => 'Instance type',
                'instance_ids' => 'Instance',
                'photo' => 'Foto',
                'password' => 'Password',
                'password_confirmation' => 'Konfirmasi password',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            $firstname = explode(' ', $request->fullname)[0];
            $lastname = explode(' ', $request->fullname)[1] ?? null;

            $data = new User();
            if (!$data) {
                return $this->errorResponse('Pengguna tidak ditemukan', 404);
            }
            $data->fullname = $request->fullname;
            $data->firstname = $firstname;
            $data->lastname = $lastname;
            $data->username = $request->username;
            $data->email = $request->email;
            $data->role_id = $request->role;
            $data->photo = 'storage/images/users/default.png';
            if ($request->password) {
                $data->password = Hash::make($request->password);
            }
            if ($request->foto) {
                $fileName = time();
                $upload = $request->foto->storeAs('images/users', $fileName . '.' . $request->foto->extension(), 'public');
                $data->photo = 'storage/' . $upload;
            }
            $data->instance_id = $request->instance_id ?? null;
            $data->instance_type = $request->instance_type ?? null;
            $data->save();

            if (!$data) {
                return $this->errorResponse('Pengguna gagal dibuat');
            }

            if ($request->role == 6) {
                if ($request->instance_ids > 0) {
                    foreach ($request->instance_ids as $value) {
                        DB::table('pivot_user_verificator_instances')
                            ->updateOrInsert(
                                ['user_id' => $data->id, 'instance_id' => $value['value']],
                                ['user_id' => $data->id, 'instance_id' => $value['value']]
                            );
                    }
                }
            }

            DB::commit();
            $returnData = [
                'id' => $data->id,
                'fullname' => $data->fullname,
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'username' => $data->username,
                'email' => $data->email,
                'role_id' => $data->role_id,
                'role_name' => DB::table('roles')->where('id', $data->role_id)->first()->display_name ?? null,
                'instance_id' => $data->instance_id,
                'instance_name' => DB::table('instances')->where('id', $data->instance_id)->first()->name ?? null,
                'instance_type' => $data->instance_type,
                'instance_ids' => $data->instance_ids,
                'photo' => $data->photo,
            ];
            return $this->successResponse($returnData, 'Pengguna berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function detailUser($id, Request $request)
    {
        try {
            if (in_array(auth()->user()->role_id, [6, 7, 8, 10, 11])) {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }
            if (auth()->user()->role_id == 9 && auth()->user()->instance_type != 'kepala') {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }
            $data = User::find($id);
            if ($data && $data->id == 1) {
                return $this->errorResponse('Pengguna tidak ditemukan', 404);
            }
            if (!$data) {
                return $this->errorResponse('Pengguna tidak ditemukan', 404);
            }
            $instanceIds = [];
            if ($data->role_id == 6) {
                $Ids = DB::table('pivot_user_verificator_instances')
                    ->where('user_id', $data->id)
                    ->get();
                foreach ($Ids as $id) {
                    $instanceIds[] = [
                        'value' => $id->instance_id,
                        'label' => DB::table('instances')->where('id', $id->instance_id)->first()->name ?? 'null',
                    ];
                }
            }
            $data = [
                'id' => $data->id,
                'fullname' => $data->fullname,
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'username' => $data->username,
                'email' => $data->email,
                'role_id' => $data->role_id,
                'role_name' => DB::table('roles')->where('id', $data->role_id)->first()->display_name ?? null,
                'instance_id' => $data->instance_id,
                'instance_name' => DB::table('instances')->where('id', $data->instance_id)->first()->name ?? null,
                'instance_type' => $data->instance_type,
                'instance_ids' => $instanceIds,
                'status' => $data->status,
                'photo' => asset($data->photo),
            ];

            return $this->successResponse($data, 'Pengguna berhasil diambil');
            return $this->successResponse(auth()->user(), 'List of users');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function updateUser($id, Request $request)
    {
        try {
            if (in_array(auth()->user()->role_id, [6, 7, 8, 10, 11])) {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }
            if (auth()->user()->role_id == 9 && auth()->user()->instance_type != 'kepala') {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }
            DB::beginTransaction();
            $validate = Validator::make($request->all(), [
                'fullname' => 'required|string',
                'firstname' => 'nullable|string',
                'lastname' => 'nullable|string',
                'username' => 'required|alpha_dash|alpha_num|unique:users,username,' . $id,
                'email' => 'required|email|unique:users,email,' . $id,
                'role' => 'required|integer|exists:roles,id',
                'instance_id' => 'nullable|integer|exists:instances,id',
                'instance_type' => 'nullable|string',
                'instance_ids' => 'nullable|array',
                'instance_ids.*' => 'nullable',
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10480',
                'password' => 'nullable|string',
                'password_confirmation' => 'nullable|string|same:password',
            ], [], [
                'fullname' => 'Nama lengkap',
                'firstname' => 'Nama depan',
                'lastname' => 'Nama belakang',
                'username' => 'Username',
                'email' => 'Email',
                'role' => 'Role',
                'instance_id' => 'Instance',
                'instance_type' => 'Instance type',
                'instance_ids' => 'Instance',
                'photo' => 'Foto',
                'password' => 'Password',
                'password_confirmation' => 'Konfirmasi password',
            ]);
            if ($validate->fails()) return $this->validationResponse($validate->errors());

            $firstname = explode(' ', $request->fullname)[0];
            $lastname = explode(' ', $request->fullname)[1] ?? null;

            $data = User::find($id);
            if (!$data) {
                return $this->errorResponse('Pengguna tidak ditemukan', 404);
            }
            $data->fullname = $request->fullname;
            $data->firstname = $firstname;
            $data->lastname = $lastname;
            $data->username = $request->username;
            $data->email = $request->email;
            $data->role_id = $request->role;
            if ($request->password) {
                $data->password = Hash::make($request->password);
            }
            if ($request->foto) {
                $fileName = time();
                $upload = $request->foto->storeAs('images/users', $fileName . '.' . $request->foto->extension(), 'public');
                $data->photo = 'storage/' . $upload;
            }
            $data->instance_id = $request->instance_id ?? null;
            $data->instance_type = $request->instance_type ?? null;
            $data->save();

            if ($request->role == 6) {
                if ($request->instance_ids > 0) {

                    DB::table('pivot_user_verificator_instances')
                        ->where('user_id', $data->id)
                        ->delete();

                    foreach ($request->instance_ids as $value) {
                        DB::table('pivot_user_verificator_instances')
                            ->updateOrInsert(
                                ['user_id' => $data->id, 'instance_id' => $value['value']],
                                ['user_id' => $data->id, 'instance_id' => $value['value']]
                            );
                    }
                }
            }

            if ($request->role == 9) {
                // DB::table('pivot_user_sub_kegiatan_permissions')
                //     ->where('user_id', $data->id)
                //     ->delete();

                // DB::table('pivot_user_sub_kegiatan_permissions')
                //     ->insert([
                //         'user_id' => $data->id,
                //         'program_id' => 57,
                //         'kegiatan_id' => 194,
                //         'sub_kegiatan_id' => 990,
                //         'periode_id' => 1,
                //     ]);
            }

            DB::commit();
            $data = [
                'id' => $data->id,
                'fullname' => $data->fullname,
                'firstname' => $data->firstname,
                'lastname' => $data->lastname,
                'username' => $data->username,
                'email' => $data->email,
                'role_id' => $data->role_id,
                'role_name' => DB::table('roles')->where('id', $data->role_id)->first()->display_name ?? null,
                'instance_id' => $data->instance_id,
                'instance_name' => DB::table('instances')->where('id', $data->instance_id)->first()->name ?? null,
                'instance_type' => $data->instance_type,
                'instance_ids' => $data->instance_ids,
                'status' => $data->status,
                'photo' => asset($data->photo),
            ];

            return $this->successResponse($data, 'Pengguna berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteUser($id)
    {
        try {
            if (in_array(auth()->user()->role_id, [6, 7, 8, 10, 11])) {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }
            if (auth()->user()->role_id == 9 && auth()->user()->instance_type != 'kepala') {
                return $this->errorResponse('Anda tidak memiliki akses', 200);
            }
            $data = User::find($id);
            if (!$data) {
                return $this->errorResponse('Pengguna tidak ditemukan', 404);
            }
            if ($data->id == 1) {
                return $this->errorResponse('Pengguna tidak ditemukan', 404);
            }
            $data->delete();
            return $this->successResponse(null, 'Pengguna berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function listInstance(Request $request)
    {
        try {
            $user = auth()->user();
            $instanceIds = [];
            if ($user->role_id == 6) {
                $Ids = DB::table('pivot_user_verificator_instances')
                    ->where('user_id', $user->id)
                    ->get();
                foreach ($Ids as $id) {
                    $instanceIds[] = $id->instance_id;
                }
            }

            $instances = Instance::search($request->search)
                ->when($user->role_id == 6, function ($query) use ($instanceIds) {
                    return $query->whereIn('id', $instanceIds);
                })
                ->when($user->role_id == 9, function ($query) use ($user) {
                    return $query->where('id', $user->instance_id);
                })
                ->with(['Programs', 'Kegiatans', 'SubKegiatans'])
                ->oldest('id')
                ->get();
            $datas = [];
            foreach ($instances as $instance) {
                $website = $instance->website;
                if ($website) {
                    if (str()->contains($website, 'http')) {
                        $website = $instance->website;
                    } else {
                        $website = 'http://' . $instance->website;
                    }
                }
                $facebook = $instance->facebook;
                if ($facebook) {
                    if (str()->contains($facebook, 'http')) {
                        $facebook = $instance->facebook;
                    } else {
                        $facebook = 'http://facebook.com/search/top/?q=' . $instance->facebook;
                    }
                }
                $instagram = $instance->instagram;
                if ($instagram) {
                    if (str()->contains($instagram, 'http')) {
                        $instagram = $instance->instagram;
                    } else {
                        $instagram = 'http://instagram.com/' . $instance->instagram;
                    }
                }
                $youtube = $instance->youtube;
                if ($youtube) {
                    if (str()->contains($youtube, 'http')) {
                        $youtube = $instance->youtube;
                    } else {
                        $youtube = 'http://youtube.com/results?search_query=' . $instance->youtube;
                    }
                }
                $datas[] = [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'alias' => $instance->alias,
                    'code' => $instance->code,
                    'logo' => asset($instance->logo),
                    'status' => $instance->status,
                    'description' => $instance->description,
                    'address' => $instance->address,
                    'phone' => $instance->phone,
                    'fax' => $instance->fax,
                    'email' => $instance->email,
                    'website' => $website,
                    'facebook' => $facebook,
                    'instagram' => $instagram,
                    'youtube' => $youtube,
                    'created_at' => $instance->created_at,
                    'updated_at' => $instance->updated_at,
                    'programs' => $instance->Programs->count(),
                    'kegiatans' => $instance->Kegiatans->count(),
                    'sub_kegiatans' => $instance->SubKegiatans->count(),
                ];
            }
            return $this->successResponse($datas, 'List of instances');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function createInstance(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'alias' => 'required|string',
            'code' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
            'status' => 'nullable|string',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'fax' => 'nullable|string',
            'email' => 'nullable|string',
            'website' => 'nullable|string',
            'facebook' => 'nullable|string',
            'instagram' => 'nullable|string',
            'youtube' => 'nullable|string',
        ], [], [
            'name' => 'Nama',
            'alias' => 'Alias',
            'code' => 'Kode',
            'logo' => 'Logo',
            'status' => 'Status',
            'description' => 'Deskripsi',
            'address' => 'Alamat',
            'phone' => 'Telepon',
            'fax' => 'Fax',
            'email' => 'Email',
            'website' => 'Website',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'youtube' => 'Youtube',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        DB::beginTransaction();
        try {

            $data = new Instance();
            $data->name = str()->upper($request->name);
            $data->alias = $request->alias;
            $data->code = $request->code;
            $data->logo = 'storage/images/pd/default.png';
            if ($request->logo) {
                $fileName = time();
                $upload = $request->logo->storeAs('images/pd', $fileName . '.' . $request->logo->extension(), 'public');
                $data->logo = 'storage/' . $upload;
            }
            $data->status = 'active';
            $data->description = $request->description ?? null;
            $data->address = $request->address ?? null;
            $data->phone = $request->phone ?? null;
            $data->fax = $request->fax ?? null;
            $data->email = $request->email ?? null;
            $data->website = $request->website ?? null;
            $data->facebook = $request->facebook ?? null;
            $data->instagram = $request->instagram ?? null;
            $data->youtube = $request->youtube ?? null;
            $data->save();

            DB::commit();
            $data = [
                'id' => $data->id,
                'name' => $data->name,
                'alias' => $data->alias,
                'code' => $data->code,
                'logo' => asset($data->logo),
                'description' => $data->description,
                'address' => $data->address,
                'phone' => $data->phone,
            ];
            return $this->successResponse($data, 'Perangkat Daerah berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function detailInstance($id, Request $request)
    {
        try {
            $data = Instance::find($id);
            if (!$data) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan', 404);
            }
            $data = [
                'id' => $data->id,
                'name' => $data->name,
                'alias' => $data->alias,
                'code' => $data->code,
                'logo' => asset($data->logo),
                'status' => $data->status,
                'description' => $data->description,
                'address' => $data->address,
                'phone' => $data->phone,
                'fax' => $data->fax,
                'email' => $data->email,
                'website' => $data->website,
                'facebook' => $data->facebook,
                'instagram' => $data->instagram,
                'youtube' => $data->youtube,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ];

            return $this->successResponse($data, 'Perangkat Daerah berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function updateInstance($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'alias' => 'required|string',
            'code' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
            'status' => 'nullable|string',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'fax' => 'nullable|string',
            'email' => 'nullable|string',
            'website' => 'nullable|string',
            'facebook' => 'nullable|string',
            'instagram' => 'nullable|string',
            'youtube' => 'nullable|string',
        ], [], [
            'name' => 'Nama',
            'alias' => 'Alias',
            'code' => 'Kode',
            'logo' => 'Logo',
            'status' => 'Status',
            'description' => 'Deskripsi',
            'address' => 'Alamat',
            'phone' => 'Telepon',
            'fax' => 'Fax',
            'email' => 'Email',
            'website' => 'Website',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'youtube' => 'Youtube',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }
        try {
            DB::beginTransaction();
            $data = Instance::find($id);
            if (!$data) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan', 404);
            }
            $data->name = str()->upper($request->name);
            $data->alias = $request->alias;
            $data->code = $request->code;
            if ($request->logo) {
                $fileName = time();
                $upload = $request->logo->storeAs('images/pd', $fileName . '.' . $request->logo->extension(), 'public');
                $data->logo = 'storage/' . $upload;
            }
            $data->description = $request->description ?? null;
            $data->address = $request->address ?? null;
            $data->phone = $request->phone ?? null;
            $data->fax = $request->fax ?? null;
            $data->email = $request->email ?? null;
            $data->website = $request->website ?? null;
            $data->facebook = $request->facebook ?? null;
            $data->instagram = $request->instagram ?? null;
            $data->youtube = $request->youtube ?? null;
            $data->save();

            DB::commit();
            $data = [
                'id' => $data->id,
                'name' => $data->name,
                'alias' => $data->alias,
                'code' => $data->code,
                'logo' => asset($data->logo),
                'description' => $data->description,
                'address' => $data->address,
                'phone' => $data->phone,
            ];
            return $this->successResponse($data, 'Perangkat Daerah berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function deleteInstance($id)
    {
        try {
            $data = Instance::find($id);
            if (!$data) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan', 404);
            }
            $data->delete();
            return $this->successResponse(null, 'Perangkat Daerah berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function instanceSubUnit($alias, Request $request)
    {
        try {
            $return = [
                'instance' => null,
                'data' => [],
                'programs' => [],
            ];
            $instance = Instance::where('alias', $alias)->first();
            if (!$instance) {
                return $this->errorResponse('Perangkat Daerah tidak ditemukan', 404);
            }

            $return['instance'] = [
                'id' => $instance->id,
                'name' => $instance->name,
                'alias' => $instance->alias,
                'code' => $instance->code,
                'logo' => asset($instance->logo),
                'status' => $instance->status,
                'description' => $instance->description,
                'address' => $instance->address,
                'phone' => $instance->phone,
                'fax' => $instance->fax,
                'email' => $instance->email,
                'website' => $instance->website,
                'facebook' => $instance->facebook,
                'instagram' => $instance->instagram,
                'youtube' => $instance->youtube,
                'created_at' => $instance->created_at,
                'updated_at' => $instance->updated_at,
            ];

            $return['programs'] = $instance->Programs->where('periode_id', $request->periode);
            $return['admins'] = User::where('instance_id', $instance->id)
                ->select('id', 'fullname', 'username', 'instance_id', 'instance_type')
                ->where('instance_type', 'staff')
                ->get();

            $subUnits = InstanceSubUnit::where('instance_id', $instance->id)
                // ->where('periode_id', $request->periode)
                ->orderBy('code')
                ->oldest()
                ->get();

            foreach ($subUnits as $sub) {
                $return['data'][] = [
                    'id' => $sub->id,
                    'type' => $sub->type,
                    'instance_id' => $sub->instance_id,
                    'name' => $sub->name,
                    'alias' => $sub->alias,
                    'code' => $sub->code,
                    'CreatedBy' => $sub->CreatedBy->fullname ?? '',
                    'UpdatedBy' => $sub->UpdatedBy->fullname ?? '',
                    'programs' => $sub->Programs->where('periode_id', $request->periode),
                    'admins' => $sub->Admins,
                ];
            }

            return $this->successResponse($return, 'Perangkat Daerah berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function instanceSubUnitDetail($alias, $id, Request $request)
    {
        try {
            $data = InstanceSubUnit::find($id);
            if (!$data) {
                return $this->errorResponse('Sub Unit tidak ditemukan', 404);
            }
            $data = [
                'id' => $data->id,
                'type' => $data->type,
                'instance_id' => $data->instance_id,
                'name' => $data->name,
                'alias' => $data->alias,
                'code' => $data->code,
                'programs' => collect($data->Programs->pluck('id'))->unique()->values(),
                'admins' => collect($data->Admins->pluck('id'))->unique()->values(),
            ];

            return $this->successResponse($data, 'Perangkat Daerah berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function instanceSubUnitStore($alias, Request $request)
    {
        if ($request->inputType == 'create') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'type' => 'required|string',
                'id' => 'nullable|numeric|exists:instance_sub_unit,id',
                'name' => 'required|string',
                'alias' => 'required|string',
                'code' => 'nullable|string',
                'programs' => 'required|array',
                'admins' => 'required|array',
            ], [], [
                'periode' => 'Periode',
                'type' => 'Jenis',
                'id' => 'Id',
                'name' => 'Nama',
                'alias' => 'Nama Alias',
                'code' => 'Kode',
                'programs' => 'Program',
                'admins' => 'Admin Data Input',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            DB::beginTransaction();
            try {
                $instance = Instance::where('alias', $alias)->first();
                if (!$instance) {
                    return $this->errorResponse('Perangkat Daerah tidak ditemukan', 200);
                }
                $subUnit = new InstanceSubUnit();
                $subUnit->type = str()->lower($request->type);
                $subUnit->instance_id = $instance->id;
                $subUnit->name = $request->name;
                $subUnit->alias = $request->alias;
                $subUnit->code = $request->code;
                $subUnit->created_by = auth()->id();
                $subUnit->save();

                foreach ($request->admins as $adm) {
                    DB::table('pivot_user_instance_sub_unit')
                        ->insert([
                            'user_id' => $adm,
                            'sub_unit_id' => $subUnit->id,
                        ]);

                    foreach ($request->programs as $prg) {
                        $program = Program::find($prg);

                        if ($subUnit->Programs->count() > 0) {
                            DB::table('pivot_instance_sub_unit_program')
                                ->where('instance_sub_unit_id', $subUnit->id)
                                ->where('periode_id', $request->periode)
                                ->delete();
                        }

                        DB::table('pivot_instance_sub_unit_program')
                            ->insert([
                                'instance_sub_unit_id' => $subUnit->id,
                                'program_id' => $program->id,
                                'periode_id' => $request->periode,
                            ]);

                        $kegiatans = Kegiatan::where('program_id', $program->id)
                            ->where('periode_id', $program->periode_id)
                            ->get();
                        foreach ($kegiatans as $keg) {
                            $subKegiatans = SubKegiatan::where('program_id', $program->id)
                                ->where('kegiatan_id', $keg->id)
                                ->where('periode_id', $program->periode_id)
                                ->get();
                            foreach ($subKegiatans as $subKeg) {

                                DB::table('pivot_user_sub_kegiatan_permissions')
                                    ->insert([
                                        'user_id' => $adm,
                                        'periode_id' => $program->periode_id,
                                        'program_id' => $program->id,
                                        'kegiatan_id' => $keg->id,
                                        'sub_kegiatan_id' => $subKeg->id,
                                    ]);
                            }
                        }
                    }
                }
                DB::commit();
                return $this->successResponse($subUnit, 'Data berhasil dibuat');
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->errorResponse($th->getMessage());
            }
        }

        if ($request->inputType == 'edit') {
            $validate = Validator::make($request->all(), [
                'periode' => 'required|numeric|exists:ref_periode,id',
                'type' => 'required|string',
                'id' => 'required|numeric|exists:instance_sub_unit,id',
                'name' => 'required|string',
                'alias' => 'required|string',
                'code' => 'nullable|string',
                'programs' => 'required|array',
                'admins' => 'required|array',
            ], [], [
                'periode' => 'Periode',
                'type' => 'Jenis',
                'id' => 'Id',
                'name' => 'Nama',
                'alias' => 'Nama Alias',
                'code' => 'Kode',
                'programs' => 'Program',
                'admins' => 'Admin Data Input',
            ]);

            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }

            // DB::beginTransaction();
            try {
                $instance = Instance::where('alias', $alias)->first();
                if (!$instance) {
                    return $this->errorResponse('Perangkat Daerah tidak ditemukan', 200);
                }
                $subUnit = InstanceSubUnit::find($request->id);
                $subUnit->instance_id = $instance->id;
                $subUnit->name = $request->name;
                $subUnit->alias = $request->alias;
                $subUnit->code = $request->code;
                $subUnit->updated_by = auth()->id();
                $subUnit->save();

                // Delete before insert
                if ($subUnit->Programs->where('periode_id', $request->periode)->count() > 0) {
                    $dts = DB::table('pivot_user_sub_kegiatan_permissions')
                        ->whereIn('program_id', $subUnit->Programs->where('periode_id', $request->periode)->pluck('id'))
                        ->delete();
                }

                if ($subUnit->Admins->count() > 0) {
                    DB::table('pivot_user_instance_sub_unit')
                        ->where('sub_unit_id', $subUnit->id)
                        ->delete();
                }

                foreach ($request->admins as $adm) {
                    DB::table('pivot_user_instance_sub_unit')
                        ->insert([
                            'user_id' => $adm,
                            'sub_unit_id' => $subUnit->id,
                        ]);

                    if ($subUnit->Programs->count() > 0) {
                        DB::table('pivot_instance_sub_unit_program')
                            ->where('instance_sub_unit_id', $subUnit->id)
                            ->where('periode_id', $request->periode)
                            ->delete();
                    }
                    foreach ($request->programs as $prg) {
                        $program = Program::find($prg);


                        DB::table('pivot_instance_sub_unit_program')
                            ->insert([
                                'instance_sub_unit_id' => $subUnit->id,
                                'program_id' => $program->id,
                                'periode_id' => $request->periode,
                            ]);

                        $kegiatans = Kegiatan::where('program_id', $program->id)
                            ->where('periode_id', $program->periode_id)
                            ->get();
                        foreach ($kegiatans as $keg) {
                            $subKegiatans = SubKegiatan::where('program_id', $program->id)
                                ->where('kegiatan_id', $keg->id)
                                ->where('periode_id', $program->periode_id)
                                ->get();
                            foreach ($subKegiatans as $subKeg) {

                                DB::table('pivot_user_sub_kegiatan_permissions')
                                    ->insert([
                                        'user_id' => $adm,
                                        'periode_id' => $program->periode_id,
                                        'program_id' => $program->id,
                                        'kegiatan_id' => $keg->id,
                                        'sub_kegiatan_id' => $subKeg->id,
                                    ]);
                            }
                        }
                    }
                }
                DB::commit();
                return $this->successResponse($subUnit, 'Data berhasil diperbarui');
            } catch (\Throwable $th) {
                DB::rollBack();
                return $this->errorResponse($th->getMessage() . ' - ' . $th->getLine());
            }
        }
    }

    function instanceSubUnitDelete($alias, $id)
    {
        try {
            $data = InstanceSubUnit::find($id);
            if (!$data) {
                return $this->errorResponse('Sub Unit tidak ditemukan', 404);
            }
            $data->delete();
            return $this->successResponse(null, 'Sub Unit berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }


    function listRefPeriode(Request $request)
    {
        try {
            $datas = DB::table('ref_periode')
                ->select('id', 'name', 'start_date', 'end_date', 'status')
                ->get();
            return $this->successResponse($datas, 'List master periode');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function listRefPeriodeRange(Request $request)
    {
        try {
            $datas = [];
            $periode = DB::table('ref_periode')
                ->where('id', $request->periode_id)
                ->first();

            // range years
            $startYear = date('Y', strtotime($periode->start_date));
            $endYear = date('Y', strtotime($periode->end_date));
            $years = range($startYear, $endYear);

            // range months
            $startMonth = date('m', strtotime($periode->start_date));
            $endMonth = date('m', strtotime($periode->end_date));
            $months = range($startMonth, $endMonth);

            // range days
            $startDay = date('d', strtotime($periode->start_date));
            $endDay = date('d', strtotime($periode->end_date));
            $days = range($startDay, $endDay);

            $datas = [
                'years' => $years,
                'months' => $months,
                'days' => $days,
            ];

            return $this->successResponse($datas, 'List range periode');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function createRefPeriode(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'start_date' => 'required|numeric|lte:end_date',
            'end_date' => 'required|numeric|gte:start_date',
            'status' => 'nullable|string',
        ], [], [
            'name' => 'Nama',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
            'status' => 'Status',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = new Periode();
            $data->name = $request->start_date . ' - ' . $request->end_date;
            $data->start_date = $request->start_date . '-01-01';
            $data->end_date = $request->end_date . '-12-31';
            $data->status = $request->status ?? 'active';
            $data->save();

            if (!$data) {
                return $this->errorResponse('Periode gagal dibuat');
            }
            DB::commit();
            return $this->successResponse($data, 'Periode berhasil dibuat');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefPeriode($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'nullable|string',
            'start_date' => 'required|numeric|lte:end_date',
            'end_date' => 'required|numeric|gte:start_date',
            'status' => 'nullable|string',
        ], [], [
            'name' => 'Nama',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
            'status' => 'Status',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = Periode::find($id);
            if (!$data) {
                return $this->errorResponse('Periode tidak ditemukan', 404);
            }
            $data->name = $request->start_date . ' - ' . $request->end_date;
            $data->start_date = $request->start_date . '-01-01';
            $data->end_date = $request->end_date . '-12-31';
            $data->status = $request->status ?? 'active';
            $data->save();

            if (!$data) {
                return $this->errorResponse('Periode gagal diperbarui');
            }
            DB::commit();
            return $this->successResponse($data, 'Periode berhasil diperbarui');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
    }

    function listRefSatuan(Request $request)
    {
        try {
            $datas = Satuan::search($request->search)
                ->get();
            return $this->successResponse($datas, 'List master satuan');
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function createRefSatuan(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
        ], [], [
            'name' => 'Nama',
            'description' => 'Deskripsi',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = new Satuan();
            $data->name = $request->name;
            $data->description = $request->description ?? null;
            $data->status = 'active';
            $data->save();

            if (!$data) {
                return $this->errorResponse('Satuan gagal dibuat');
            }
            DB::commit();
            return $this->successResponse($data, 'Satuan berhasil dibuat');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
    }

    function updateRefSatuan($id, Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
        ], [], [
            'name' => 'Nama',
            'description' => 'Deskripsi',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $data = Satuan::find($id);
            if (!$data) {
                return $this->errorResponse('Satuan tidak ditemukan', 404);
            }
            $data->name = $request->name;
            $data->description = $request->description ?? null;
            $data->save();

            if (!$data) {
                return $this->errorResponse('Satuan gagal diperbarui');
            }
            DB::commit();
            return $this->successResponse($data, 'Satuan berhasil diperbarui');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
    }

    function deleteRefSatuan($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $data = Satuan::find($id);
            if (!$data) {
                return $this->errorResponse('Satuan tidak ditemukan', 404);
            }
            $data->delete();
            DB::commit();
            return $this->successResponse(null, 'Satuan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }
    }

    function listKodeRekening(Request $request)
    {
        try {
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    function localListInstance(Request $request)
    {
        try {
            $instanceIds = [];
            $instances = Instance::search($request->search)
                ->with(['Programs', 'Kegiatans', 'SubKegiatans'])
                ->oldest('id')
                ->get();
            $datas = [];
            foreach ($instances as $instance) {
                $website = $instance->website;
                if ($website) {
                    if (str()->contains($website, 'http')) {
                        $website = $instance->website;
                    } else {
                        $website = 'http://' . $instance->website;
                    }
                }
                $facebook = $instance->facebook;
                if ($facebook) {
                    if (str()->contains($facebook, 'http')) {
                        $facebook = $instance->facebook;
                    } else {
                        $facebook = 'http://facebook.com/search/top/?q=' . $instance->facebook;
                    }
                }
                $instagram = $instance->instagram;
                if ($instagram) {
                    if (str()->contains($instagram, 'http')) {
                        $instagram = $instance->instagram;
                    } else {
                        $instagram = 'http://instagram.com/' . $instance->instagram;
                    }
                }
                $youtube = $instance->youtube;
                if ($youtube) {
                    if (str()->contains($youtube, 'http')) {
                        $youtube = $instance->youtube;
                    } else {
                        $youtube = 'http://youtube.com/results?search_query=' . $instance->youtube;
                    }
                }
                $datas[] = [
                    'id' => $instance->id,
                    'id_eoffice' => $instance->id_eoffice,
                    'name' => $instance->name,
                    'alias' => $instance->alias,
                    'code' => $instance->code,
                    'logo' => asset($instance->logo),
                    'website' => $website,
                    'facebook' => $facebook,
                    'instagram' => $instagram,
                    'youtube' => $youtube,
                    'programs' => $instance->Programs->count(),
                    'kegiatans' => $instance->Kegiatans->count(),
                    'sub_kegiatans' => $instance->SubKegiatans->count(),
                ];
            }
            return $this->successResponse($datas, 'List of instances');
        } catch (\Exception $e) {
            DB::table('error_logs')
                ->insertOrIgnore([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            // return $this->errorResponse($e->getMessage());
        }
    }
}
