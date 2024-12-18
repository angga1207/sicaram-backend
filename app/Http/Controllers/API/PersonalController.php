<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use Jenssegers\Agent\Agent;
use App\Traits\JsonReturner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PersonalController extends Controller
{
    use JsonReturner;

    function updateFcmToken($id, Request $request)
    {
        try {
            $validate = Validator::make($request->all(), [
                'fcmToken' => 'required|string',
            ], [], [
                'fcmToken' => 'FCM Token',
            ]);
            if ($validate->fails()) {
                return $this->validationResponse($validate->errors());
            }
            $data = User::find($id);
            if (!$data) {
                return $this->errorResponse('Pengguna tidak ditemukan', 200);
            }
            $data->fcm_token = $request->fcmToken;
            $data->save();
            return $this->successResponse($data, 'FCM Token berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    function detailMe(Request $request)
    {
        try {
            $data = User::find(auth()->user()->id);
            if (!$data) {
                return $this->errorResponse('Pengguna tidak ditemukan', 200);
            }
            $userLogs = DB::table('log_users')
                ->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')])
                ->where('user_id', auth()->id())
                ->latest('date')
                ->paginate(5);
            foreach ($userLogs as $key => $value) {
                $userLogs[$key]->logs = $value->logs ? json_decode($value->logs, true) : [];
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
                'status' => $data->status,
                'photo' => asset($data->photo),
                'userLogs' => $userLogs,

                'MyPermissions' => $data->MyPermissions(),
            ];

            return $this->successResponse($data, 'Pengguna berhasil diambil');
        } catch (\Exception $e) {
            DB::table('error_logs')
                ->insert([
                    'user_id' => auth()->id() ?? null,
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'log' => $e,
                    'file' => $e->getFile(),
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'status' => 'unread',
                ]);
            // return $this->errorResponse('Terjadi Kesalahan pada Server, Harap Hubungi Admin!');
            return $this->errorResponse($e->getMessage());
        }
    }

    function Logs(Request $request)
    {
        try {
            $data = User::find(auth()->user()->id);
            if (!$data) {
                return $this->errorResponse('Pengguna tidak ditemukan', 200);
            }
            $userLogs = DB::table('log_users')
                ->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')])
                ->where('user_id', auth()->id())
                // ->where('user_id', auth()->user()->huwqhasdas)
                ->latest('date')
                ->select(['id', 'ip_address', 'user_agent', 'date', 'logs', 'created_at', 'updated_at'])
                ->paginate(5);

            foreach ($userLogs as $key => $value) {
                // $logs = $value->logs ? json_decode($value->logs, true) : [];
                $logs = $value->logs ? json_decode($value->logsz, true) : [];
                $logs = collect($logs);
                if (count($logs) > 0) {
                    $logs = $logs->sortByDesc('created_at');
                }
                $logs = $logs->values()->all();

                $userLogs[$key]->logs = $logs;

                $agent = new Agent();
                $agent->setUserAgent($value->user_agent);
                $userLogs[$key]->device = $agent->device();
                $userLogs[$key]->platform = $agent->platform();
                $userLogs[$key]->browser = $agent->browser();
                $userLogs[$key]->isDesktop = $agent->isDesktop();
                $userLogs[$key]->isMobile = $agent->isMobile();
                $userLogs[$key]->isTablet = $agent->isTablet();
                $userLogs[$key]->isPhone = $agent->isPhone();
            }

            return $this->successResponse($userLogs, 'Pengguna berhasil diambil');
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

    function notifications(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);
            if (!$user) {
                return $this->errorResponse('Pengguna tidak ditemukan', 200);
            }

            $notifications = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->latest('created_at')
                ->paginate(5);

            $return = [];
            $return['current_page'] = $notifications->currentPage();
            foreach ($notifications as $key => $value) {
                $payload = json_decode($value->data, true);
                $fromUser = User::find($payload['byUserId']);
                $uri = null;
                if ($payload['uri']) {
                    $uri = $payload['uri'];
                }
                $return['data'][] = [
                    'id' => $value->id,
                    'photo' => $fromUser ? asset($fromUser->photo) : null,
                    'fullname' => $fromUser ? $fromUser->fullname : 'System',
                    'user_instance' => $fromUser->Instance ? $fromUser->Instance->name : 'System',
                    'user_instance_alias' => $fromUser->Instance ? $fromUser->Instance->alias : 'System',
                    'user_role' => $fromUser->Role ? $fromUser->Role->display_name : 'System',
                    'title' => $payload['title'],
                    'message' => $payload['message'],
                    'time' => Carbon::parse($value->created_at)->isoFormat('D MMM Y - HH:mm') . ' WIB',
                    'date' => $value->created_at,
                    'read' => $value->read_at ? true : false,
                    'modelId' => $payload['modelId'],
                    'type' => $payload['type'],
                    'uri' => $uri,
                ];
            }

            return $this->successResponse($return, 'Notifikasi berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
        }
    }

    function notificationsLess(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);
            if (!$user) {
                return $this->errorResponse('Pengguna tidak ditemukan', 200);
            }

            $notifications = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                // ->where('read_at', null)
                ->latest('created_at')
                ->limit(5)
                ->get();

            $return = [];
            foreach ($notifications as $key => $value) {
                $payload = json_decode($value->data, true);
                $fromUser = User::find($payload['byUserId']);

                $uri = null;
                if ($payload['uri']) {
                    $uri = $payload['uri'];
                }
                $return[] = [
                    'id' => $value->id,
                    'profile' => $fromUser ? asset($fromUser->photo) : null,
                    'title' => $payload['title'],
                    'message' => $payload['message'],
                    'time' => Carbon::parse($value->created_at)->diffForHumans(),
                    'read' => $value->read_at ? true : false,
                    'modelId' => $payload['modelId'],
                    'type' => $payload['type'],
                    'uri' => $uri,
                ];
            }

            return $this->successResponse($return, 'Notifikasi berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
        }
    }

    function markNotifAsRead($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::find(auth()->user()->id);
            if (!$user) {
                return $this->errorResponse('Pengguna tidak ditemukan', 200);
            }

            $notification = DB::table('notifications')
                ->where('id', $id)
                ->where('notifiable_id', $user->id)
                ->first();
            if (!$notification) {
                return $this->errorResponse('Notifikasi tidak ditemukan', 200);
            }

            DB::table('notifications')
                ->where('id', $id)
                ->update([
                    'read_at' => date('Y-m-d H:i:s'),
                ]);


            DB::commit();
            return $this->successResponse(null, 'Notifikasi berhasil ditandai sebagai sudah dibaca');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }

    function savePassword(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string',
            'password_confirmation' => 'required|string|same:password',
        ], [], [
            'old_password' => 'Password Lama',
            'password' => 'Password Baru',
            'password_confirmation' => 'Konfirmasi Password',
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors());
        }

        DB::beginTransaction();
        try {
            $user = User::find(auth()->user()->id);
            if (!$user) {
                return $this->errorResponse('Pengguna tidak ditemukan', 200);
            }

            if (!password_verify($request->old_password, $user->password)) {
                return $this->errorResponse('Password tidak boleh sama dengan password lama', 200);
            }

            $user->password = bcrypt($request->password);
            $user->save();

            DB::commit();
            return $this->successResponse(null, 'Password berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }
    }
}
