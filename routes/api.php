<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\API\LPSEController;
use App\Http\Controllers\API\SIPDController;
use App\Http\Controllers\API\GlobalController;
use App\Http\Controllers\API\ImportController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\TestingController;
use App\Http\Controllers\API\PersonalController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\RealisasiController;
use App\Http\Controllers\API\ReferencesController;
use App\Http\Controllers\API\MasterCaramController;
use App\Http\Controllers\API\AuthenticateController;
use App\Http\Controllers\API\PohonKinerjaController;
use App\Http\Controllers\API\TargetKinerjaController;
use App\Http\Controllers\API\TujuanSasaranController;
use App\Http\Controllers\API\Accountancy\LRAController;
use App\Http\Controllers\API\TaggingSumberDanaController;
use App\Http\Controllers\API\TargetTujuanSasaranController;
use App\Http\Controllers\API\Accountancy\AdminOnlyController;
use App\Http\Controllers\API\Accountancy\PersediaanController;
use App\Http\Controllers\API\RealisasiTujuanSasaranController;
use App\Http\Controllers\API\Accountancy\HutangBelanjaController;
use App\Http\Controllers\API\Accountancy\RekonsiliasiAsetController;
use App\Http\Controllers\Api\Accountancy\BelanjaBayarDimukaController;
use App\Http\Controllers\API\Accountancy\PenyesuaianAsetDanBebanController;

// Route::get('/testing', [TestingController::class, 'index']);

// Route::middleware(['guest.or.auth'])->group(function () {
// });
Route::post('/bdsm', [AuthenticateController::class, 'serverCheck']);
Route::get('/bdsm', [AuthenticateController::class, 'serverCheck']);
Route::post('/aioe', [GlobalController::class, 'main']);

// Login
Route::post('/login', [AuthenticateController::class, 'login'])->name('login');

// REPORT PDF
Route::get('/report/pdf/realisasi', [ReportController::class, 'reportRealisasiPDF'])->name('report.realisasi.pdf');

Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::get('/logout', [AuthenticateController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard/chart-realisasi', [DashboardController::class, 'chartRealisasi'])->name('dashboard.realisasi.chart');
    Route::get('/dashboard/summary-realisasi', [DashboardController::class, 'summaryRealisasi'])->name('dashboard.realisasi.summary');
    Route::get('/dashboard/chart-kinerja', [DashboardController::class, 'chartKinerja'])->name('dashboard.kinerja.chart');
    Route::get('/dashboard/summary-kinerja', [DashboardController::class, 'summaryKinerja'])->name('dashboard.kinerja.summary');
    Route::get('/dashboard/rank-instance', [DashboardController::class, 'rankInstance'])->name('dashboard.rank-instance');
    Route::get('/dashboard/instance/{alias}', [DashboardController::class, 'detailInstance'])->name('dashboard.instance.detail');
    Route::get('/dashboard/instance/{alias}/detail/prg/{id}', [DashboardController::class, 'detailProgramInstance'])->name('dashboard.instance.detailPrg');
    Route::get('/dashboard/instance/{alias}/detail/kgt/{id}', [DashboardController::class, 'detailKegiatanInstance'])->name('dashboard.instance.detailKgt');
    Route::get('/dashboard/instance/{alias}/detail/skgt/{id}', [DashboardController::class, 'detailSubKegiatanInstance'])->name('dashboard.instance.detailSubKgt');

    // LPSE
    Route::get('/lpse', [LPSEController::class, 'index']);
    Route::get('/lpse/getPenyediaTerumumkan', [LPSEController::class, 'getPenyediaTerumumkan']);
    Route::get('/lpse/getPenyediaTerumumkan/{kd_satker}', [LPSEController::class, 'getPenyediaTerumumkanPD']);
    Route::get('/lpse/getPenyediaTerumumkan/{kd_satker}/{kd_rup}', [LPSEController::class, 'getPenyediaTerumumkanPDDetail']);
    Route::get('/lpse/getSwakelolaTerumumkan', [LPSEController::class, 'getSwakelolaTerumumkan']);
    Route::get('/lpse/getSwakelolaTerumumkan/{kd_satker}', [LPSEController::class, 'getSwakelolaTerumumkanPD']);
    Route::get('/lpse/getSwakelolaTerumumkan/{kd_satker}/{kd_rup}', [LPSEController::class, 'getSwakelolaTerumumkanPDDetail']);

    // Roles
    Route::get('roles', [BaseController::class, 'listRole'])->name('roles.list');
    Route::post('roles', [BaseController::class, 'createRole'])->name('roles.create');
    Route::get('roles/{id}', [BaseController::class, 'detailRole'])->name('roles.detail');
    Route::post('roles/{id}', [BaseController::class, 'updateRole'])->name('roles.update');
    Route::delete('roles/{id}', [BaseController::class, 'deleteRole'])->name('roles.delete');

    // Users Resources
    Route::get('users', [BaseController::class, 'listUser'])->name('users.list');
    Route::post('users', [BaseController::class, 'createUser'])->name('users.create');
    Route::get('users/{id}', [BaseController::class, 'detailUser'])->name('users.detail');
    Route::post('users/{id}', [BaseController::class, 'updateUser'])->name('users.update');
    Route::delete('users/{id}', [BaseController::class, 'deleteUser'])->name('users.delete');

    // Personal Profile
    Route::post('users/{id}/fcm', [PersonalController::class, 'updateFcmToken']);
    Route::get('users-me', [PersonalController::class, 'detailMe']);
    Route::get('users-me/logs', [PersonalController::class, 'Logs']);
    Route::get('users-me/notifications', [PersonalController::class, 'notifications']);
    Route::get('users-me/notifications-less', [PersonalController::class, 'notificationsLess']);
    Route::get('users-me/mark-notif-as-read/{id}', [PersonalController::class, 'markNotifAsRead']);
    Route::post('users-me/save-password', [PersonalController::class, 'savePassword']);

    // Instances Resources
    Route::get('instances', [BaseController::class, 'listInstance'])->name('instances.list');
    Route::post('instances', [BaseController::class, 'createInstance'])->name('instances.create');
    Route::get('instances/{id}', [BaseController::class, 'detailInstance'])->name('instances.detail');
    Route::post('instances/{id}', [BaseController::class, 'updateInstance'])->name('instances.update');
    Route::delete('instances/{id}', [BaseController::class, 'deleteInstance'])->name('instances.delete');

    Route::get('instances/{alias}/sub_unit', [BaseController::class, 'instanceSubUnit'])->name('instances.sub-unit');
    Route::post('instances/{alias}/sub_unit', [BaseController::class, 'instanceSubUnitStore'])->name('instances.sub-unit.store');
    Route::get('instances/{alias}/sub_unit/{id}', [BaseController::class, 'instanceSubUnitDetail'])->name('instances.sub-unit.detail');
    Route::delete('instances/{alias}/sub_unit/{id}', [BaseController::class, 'instanceSubUnitDelete'])->name('instances.sub-unit.delete');

    // Ref Satuan Resources
    Route::get('ref-satuan', [BaseController::class, 'listRefSatuan'])->name('ref-satuan.list');
    Route::post('ref-satuan', [BaseController::class, 'createRefSatuan'])->name('ref-satuan.create');
    Route::get('ref-satuan/{id}', [BaseController::class, 'detailRefSatuan'])->name('ref-satuan.detail');
    Route::post('ref-satuan/{id}', [BaseController::class, 'updateRefSatuan'])->name('ref-satuan.update');
    Route::delete('ref-satuan/{id}', [BaseController::class, 'deleteRefSatuan'])->name('ref-satuan.delete');

    // Ref Periode Resources
    Route::get('ref-periode', [BaseController::class, 'listRefPeriode'])->name('ref-periode.list');
    Route::get('ref-periode-range', [BaseController::class, 'listRefPeriodeRange'])->name('ref-periode.listRange');
    Route::post('ref-periode', [BaseController::class, 'createRefPeriode'])->name('ref-periode.create');
    Route::get('ref-periode/{id}', [BaseController::class, 'detailRefPeriode'])->name('ref-periode.detail');
    Route::post('ref-periode/{id}', [BaseController::class, 'updateRefPeriode'])->name('ref-periode.update');
    Route::delete('ref-periode/{id}', [BaseController::class, 'deleteRefPeriode'])->name('ref-periode.delete');

    // Master Urusan Resources
    Route::get('ref-urusan', [MasterCaramController::class, 'listRefUrusan'])->name('ref-urusan.list');
    Route::post('ref-urusan', [MasterCaramController::class, 'createRefUrusan'])->name('ref-urusan.create');
    Route::get('ref-urusan/{id}', [MasterCaramController::class, 'detailRefUrusan'])->name('ref-urusan.detail');
    Route::post('ref-urusan/{id}', [MasterCaramController::class, 'updateRefUrusan'])->name('ref-urusan.update');
    Route::delete('ref-urusan/{id}', [MasterCaramController::class, 'deleteRefUrusan'])->name('ref-urusan.delete');

    // Master Bidang Resources
    Route::get('ref-bidang', [MasterCaramController::class, 'listRefBidang'])->name('ref-bidang.list');
    Route::post('ref-bidang', [MasterCaramController::class, 'createRefBidang'])->name('ref-bidang.create');
    Route::get('ref-bidang/{id}', [MasterCaramController::class, 'detailRefBidang'])->name('ref-bidang.detail');
    Route::post('ref-bidang/{id}', [MasterCaramController::class, 'updateRefBidang'])->name('ref-bidang.update');
    Route::delete('ref-bidang/{id}', [MasterCaramController::class, 'deleteRefBidang'])->name('ref-bidang.delete');

    // Master Program Resources
    Route::get('ref-program', [MasterCaramController::class, 'listRefProgram'])->name('ref-program.list');
    Route::post('ref-program', [MasterCaramController::class, 'createRefProgram'])->name('ref-program.create');
    Route::get('ref-program/{id}', [MasterCaramController::class, 'detailRefProgram'])->name('ref-program.detail');
    Route::post('ref-program/{id}', [MasterCaramController::class, 'updateRefProgram'])->name('ref-program.update');
    Route::delete('ref-program/{id}', [MasterCaramController::class, 'deleteRefProgram'])->name('ref-program.delete');

    // Master Kegiatan Resources
    Route::get('ref-kegiatan', [MasterCaramController::class, 'listRefKegiatan'])->name('ref-kegiatan.list');
    Route::post('ref-kegiatan', [MasterCaramController::class, 'createRefKegiatan'])->name('ref-kegiatan.create');
    Route::get('ref-kegiatan/{id}', [MasterCaramController::class, 'detailRefKegiatan'])->name('ref-kegiatan.detail');
    Route::post('ref-kegiatan/{id}', [MasterCaramController::class, 'updateRefKegiatan'])->name('ref-kegiatan.update');
    Route::delete('ref-kegiatan/{id}', [MasterCaramController::class, 'deleteRefKegiatan'])->name('ref-kegiatan.delete');

    // Master Sub Kegiatan Resources
    Route::get('ref-sub-kegiatan', [MasterCaramController::class, 'listRefSubKegiatan'])->name('ref-sub-kegiatan.list');
    Route::post('ref-sub-kegiatan', [MasterCaramController::class, 'createRefSubKegiatan'])->name('ref-sub-kegiatan.create');
    Route::get('ref-sub-kegiatan/{id}', [MasterCaramController::class, 'detailRefSubKegiatan'])->name('ref-sub-kegiatan.detail');
    Route::post('ref-sub-kegiatan/{id}', [MasterCaramController::class, 'updateRefSubKegiatan'])->name('ref-sub-kegiatan.update');
    Route::delete('ref-sub-kegiatan/{id}', [MasterCaramController::class, 'deleteRefSubKegiatan'])->name('ref-sub-kegiatan.delete');

    // Master Ref Indikator Kegiatan Resources
    Route::get('ref-indikator-kegiatan', [MasterCaramController::class, 'listRefIndikatorKegiatan'])->name('ref-indikator-kegiatan.list');
    Route::post('ref-indikator-kegiatan', [MasterCaramController::class, 'createRefIndikatorKegiatan'])->name('ref-indikator-kegiatan.create');
    Route::get('ref-indikator-kegiatan/{id)', [MasterCaramController::class, 'detailRefIndikatorKegiatan'])->name('ref-indikator-kegiatan.detail');
    Route::post('ref-indikator-kegiatan/{id}', [MasterCaramController::class, 'updateRefIndikatorKegiatan'])->name('ref-indikator-kegiatan.update');
    Route::delete('ref-indikator-kegiatan/{id}', [MasterCaramController::class, 'deleteRefIndikatorKegiatan'])->name('ref-indikator-kegiatan.delete');

    // Caram Master Rekening
    Route::get('ref-rekening', [MasterCaramController::class, 'listRekening'])->name('caram-rekening.list');
    Route::post('ref-rekening', [MasterCaramController::class, 'createRekening'])->name('caram-rekening.create');
    Route::get('ref-rekening/{id}', [MasterCaramController::class, 'detailRekening'])->name('caram-rekening.detail');
    Route::post('ref-rekening/{id}', [MasterCaramController::class, 'updateRekening'])->name('caram-rekening.update');
    Route::delete('ref-rekening/{id}', [MasterCaramController::class, 'deleteRekening'])->name('caram-rekening.delete');
    Route::post('ref-rekening-upload', [MasterCaramController::class, 'uploadRekening'])->name('caram-rekening.upload');

    Route::post('ref-rekening-upload', [MasterCaramController::class, 'uploadRekening'])->name('caram-rekening.upload');

    // Caram Master Sumber Dana
    Route::get('ref-sumber-dana', [MasterCaramController::class, 'listSumberDana'])->name('caram-sumber-dana.list');
    Route::post('ref-sumber-dana-upload', [MasterCaramController::class, 'uploadSumberDana'])->name('caram-sumber-dana.upload');

    // Caram Ref Tag Sumber Dana
    Route::get('ref-tag-sumber-dana', [ReferencesController::class, 'listTagSumberDana'])->name('ref-tag-sumber-dana.list');
    Route::post('ref-tag-sumber-dana', [ReferencesController::class, 'saveTagSumberDana'])->name('ref-tag-sumber-dana.save');
    Route::delete('ref-tag-sumber-dana/{id}', [ReferencesController::class, 'deleteTagSumberDana'])->name('ref-tag-sumber-dana.delete');

    // Master Ref Indikator Sub Kegiatan Resources
    Route::get('ref-indikator-sub-kegiatan', [MasterCaramController::class, 'listRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.list');
    Route::post('ref-indikator-sub-kegiatan', [MasterCaramController::class, 'createRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.create');
    Route::get('ref-indikator-sub-kegiatan/{id}', [MasterCaramController::class, 'detailRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.detail');
    Route::post('ref-indikator-sub-kegiatan/{id}', [MasterCaramController::class, 'updateRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.update');
    Route::delete('ref-indikator-sub-kegiatan/{id}', [MasterCaramController::class, 'deleteRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.delete');

    Route::post('ref-sub-to-rekening-upload', [SIPDController::class, 'uploadSubToRekening'])->name('caram-sub-to-rekening.upload');

    // Referensi Indikator Tujuan Sasaran Resources
    Route::get('ref-indikator-tujuan-sasaran', [TujuanSasaranController::class, 'listRefIndikatorTujuanSasaran'])->name('ref-indikator-tujuan-sasaran.list');
    Route::get('ref-indikator-tujuan-sasaran/{id}', [TujuanSasaranController::class, 'detailRefIndikatorTujuanSasaran'])->name('ref-indikator-tujuan-sasaran.detail');
    Route::post('ref-indikator-tujuan-sasaran', [TujuanSasaranController::class, 'saveRefIndikatorTujuanSasaran'])->name('ref-indikator-tujuan-sasaran.save');
    Route::delete('ref-indikator-tujuan-sasaran/{id}', [TujuanSasaranController::class, 'deleteRefIndikatorTujuanSasaran'])->name('ref-indikator-tujuan-sasaran.delete');

    // Referensi Tujuan dan Sasaran Resources
    Route::get('ref-tujuan-sasaran', [TujuanSasaranController::class, 'listRefTujuanSasaran'])->name('ref-tujuan-sasaran.list');
    Route::get('ref-tujuan-sasaran/{id}', [TujuanSasaranController::class, 'detailRefTujuanSasaran'])->name('ref-tujuan-sasaran.detail');
    Route::post('ref-tujuan-sasaran', [TujuanSasaranController::class, 'saveRefTujuanSasaran'])->name('ref-tujuan-sasaran.save');
    Route::delete('ref-tujuan-sasaran/{id}', [TujuanSasaranController::class, 'deleteRefTujuanSasaran'])->name('ref-tujuan-sasaran.delete');

    // Master Tujuan Sasaran Resources
    Route::get('master-tujuan', [TujuanSasaranController::class, 'getMasterTujuan'])->name('master-tujuan.index');
    Route::post('master-tujuan', [TujuanSasaranController::class, 'saveMasterTujuan'])->name('master-tujuan.save');
    Route::get('master-tujuan/{id}', [TujuanSasaranController::class, 'getDetailMasterTujuan'])->name('master-tujuan.detail');
    Route::delete('master-tujuan/{id}', [TujuanSasaranController::class, 'deleteMasterTujuan'])->name('master-tujuan.delete');
    Route::get('master-sasaran/{id}', [TujuanSasaranController::class, 'getDetailMasterSasaran'])->name('master-sasaran.detail');
    Route::post('master-sasaran', [TujuanSasaranController::class, 'saveMasterSasaran'])->name('master-sasaran.save');
    Route::delete('master-sasaran/{id}', [TujuanSasaranController::class, 'deleteMasterSasaran'])->name('master-sasaran.delete');

    // Target Tujuan Sasaran
    Route::get('target-tujuan-sasaran-list', [TargetTujuanSasaranController::class, 'index']);
    Route::get('target-tujuan-sasaran/{id}', [TargetTujuanSasaranController::class, 'getDetail']);
    Route::post('target-tujuan-sasaran/{id}', [TargetTujuanSasaranController::class, 'update']);

    // Target Perubahan Tujuan Sasaran
    Route::get('target-perubahan-tujuan-sasaran-list', [TargetTujuanSasaranController::class, 'indexPerubahan']);
    Route::get('target-perubahan-tujuan-sasaran/{id}', [TargetTujuanSasaranController::class, 'getDetailPerubahan']);
    Route::post('target-perubahan-tujuan-sasaran/{id}', [TargetTujuanSasaranController::class, 'updatePerubahan']);

    // Realisasi Tujuan Sasaran
    Route::get('realisasi/tujuan-sasaran-list', [RealisasiTujuanSasaranController::class, 'index']);
    Route::get('realisasi/tujuan-sasaran/{id}', [RealisasiTujuanSasaranController::class, 'getDetail']);
    Route::post('realisasi/tujuan-sasaran/{id}', [RealisasiTujuanSasaranController::class, 'updated']);

    // Pohon Kinerja
    Route::get('pohon-kinerja-list', [PohonKinerjaController::class, 'index']);
    Route::post('pohon-kinerja-list/{id}', [PohonKinerjaController::class, 'save']);
    Route::post('pohon-kinerja-list/{id}/delete', [PohonKinerjaController::class, 'delete']);

    // Caram RPJMD Resources
    Route::get('caram/rpjmd', [MasterCaramController::class, 'listCaramRPJMD'])->name('caram-rpjmd.list');
    Route::post('caram/rpjmd', [MasterCaramController::class, 'storeCaramRPJMD'])->name('caram-rpjmd.store');

    // Caram Renstra Resources
    Route::get('caram/renstra', [MasterCaramController::class, 'listCaramRenstra'])->name('caram-renstra.list');
    Route::get('caram/renstra/{id}', [MasterCaramController::class, 'detailCaramRenstra'])->name('caram-renstra.detail');
    Route::post('caram/renstra/{id}', [MasterCaramController::class, 'saveCaramRenstra'])->name('caram-renstra.save');
    Route::get('caram/renstra/{id}/notes', [MasterCaramController::class, 'listCaramRenstraNotes'])->name('caram-renstra.notes.list');
    Route::post('caram/renstra/{id}/notes', [MasterCaramController::class, 'postCaramRenstraNotes'])->name('caram-renstra.notes.post');

    // Caram Renja Resources
    Route::get('caram/renja', [MasterCaramController::class, 'listCaramRenja'])->name('caram-renja.list');
    Route::get('caram/renja/{id}', [MasterCaramController::class, 'detailCaramRenja'])->name('caram-renja.detail');
    Route::post('caram/renja/{id}', [MasterCaramController::class, 'saveCaramRenja'])->name('caram-renja.save');
    Route::get('caram/renja/{id}/notes', [MasterCaramController::class, 'listCaramRenjaNotes'])->name('caram-renja.notes.list');
    Route::post('caram/renja/{id}/notes', [MasterCaramController::class, 'postCaramRenjaNotes'])->name('caram-renja.notes.post');

    // Caram APBD Resources
    Route::get('caram/ref-apbd', [MasterCaramController::class, 'listPickProgramForApbd'])->name('caram-apbd.pre-list');
    Route::get('caram/apbd', [MasterCaramController::class, 'listCaramAPBD'])->name('caram-apbd.list');
    Route::get('caram/apbd/{id}', [MasterCaramController::class, 'detailCaramApbd'])->name('caram-apbd.detail');
    Route::post('caram/apbd/{id}', [MasterCaramController::class, 'saveCaramApbd'])->name('caram-apbd.save');
    Route::get('caram/apbd/{id}/notes', [MasterCaramController::class, 'listCaramApbdNotes'])->name('caram-apbd.notes.list');
    Route::post('caram/apbd/{id}/notes', [MasterCaramController::class, 'postCaramApbdNotes'])->name('caram-apbd.notes.post');
    Route::post('caram/upload-apbd', [SIPDController::class, 'uploadApbdFromRekapV5'])->name('caram-apbd.uploadRekapV5');

    // SIPD Upload Logs
    Route::get('/sipd/listLogs', [SIPDController::class, 'listLogs']);


    // APIS REALISASI VERSI 1 START -------------->>>>>>>>
    // Caram Realisasi Program Resources
    Route::get('caram/realisasi/listInstance', [RealisasiController::class, 'listInstance'])->name('caram-realisasi.listInstance');
    Route::get('caram/realisasi/listProgramsSubKegiatan', [RealisasiController::class, 'listProgramsSubKegiatan'])->name('caram-realisasi.listProgramsSubKegiatan');

    // Caram Tagging Sumber Dana
    Route::get('/caram/tagging-sumber-dana', [TaggingSumberDanaController::class, 'index'])->name('caram.tagging-sumber-dana.index');
    Route::get('/caram/tagging-sumber-dana/{id}', [TaggingSumberDanaController::class, 'detail'])->name('caram.tagging-sumber-dana.detail');
    Route::post('/caram/tagging-sumber-dana/{id}', [TaggingSumberDanaController::class, 'save'])->name('caram.tagging-sumber-dana.save');

    // Caram Target Kinerja
    Route::get('/caram/target-kinerja/{id}', [TargetKinerjaController::class, 'detailTargetKinerja'])->name('caram.target-kinerja.detail');
    Route::get('/caram/target-kinerja/{id}/logs', [TargetKinerjaController::class, 'logsTargetKinerja'])->name('caram.target-kinerja.logs');
    Route::post('/caram/target-kinerja/{id}', [TargetKinerjaController::class, 'saveTargetKinerja'])->name('caram.target-kinerja.save');
    Route::delete('/caram/target-kinerja-delete/{id}', [TargetKinerjaController::class, 'deleteTargetKinerja'])->name('caram.target-kinerja.deleteTargetKinerja');
    Route::delete('/caram/target-kinerja-delete-rincian/{id}', [TargetKinerjaController::class, 'deleteRincian'])->name('caram.target-kinerja.deleteRincian');
    Route::post('/caram/target-kinerja/{id}/logs', [TargetKinerjaController::class, 'postLogsTargetKinerja'])->name('caram.target-kinerja.postLogs');

    // Caram Realisasi
    Route::get('/caram/realisasi/{id}', [RealisasiController::class, 'detailRealisasi'])->name('caram.realisasi.detail');

    Route::get('/caram/realisasi-keterangan/{idRealisasiSubKegiatan}/get', [RealisasiController::class, 'getKeteranganSubKegiatan']);
    Route::post('/caram/realisasi-keterangan/{idRealisasiSubKegiatan}/save', [RealisasiController::class, 'saveKeteranganSubKegiatan']);
    Route::delete('/caram/realisasi-keterangan-delete-file/{id}', [RealisasiController::class, 'deleteImageKeteranganSubKegiatan']);
    Route::get('/caram/realisasi-keterangan-fetch-spse-kontrak', [RealisasiController::class, 'fetchSpseKontrak']);
    Route::get('/caram/realisasi-keterangan-get-kontrak', [RealisasiController::class, 'getActiveContract']);
    Route::post('/caram/realisasi-keterangan-add-kontrak', [RealisasiController::class, 'addKontrak']);
    Route::delete('/caram/realisasi-keterangan-delete-kontrak/{subKegiatanId}', [RealisasiController::class, 'deleteKontrak']);

    Route::post('/caram/realisasi/{id}', [RealisasiController::class, 'saveRealisasi'])->name('caram.realisasi.save');
    Route::post('/caram/realisasi/{id}/detail', [RealisasiController::class, 'saveDetailRealisasi'])->name('caram.realisasi.saveDetail');
    Route::get('/caram/realisasi/{id}/logs', [RealisasiController::class, 'logsRealisasi'])->name('caram.realisasi.logs');
    Route::post('/caram/realisasi/{id}/logs', [RealisasiController::class, 'postLogsRealisasi'])->name('caram.realisasi.postLogs');

    Route::post('/caram/realisasi/{id}/sync', [RealisasiController::class, 'syncRealisasi'])->name('caram.realisasi.sync');
    Route::post('/caram/realisasi/{id}/detail/sync', [RealisasiController::class, 'syncDetailRealisasi'])->name('caram.realisasi.syncDetail');

    // Caram Report
    Route::get('/report/getRefs', [ReportController::class, 'getRefs']);
    Route::get('/report/realisasi-head', [ReportController::class, 'reportRealisasiHead'])->name('report.realisasi.head');
    Route::get('/report/realisasi', [ReportController::class, 'reportRealisasi'])->name('report.realisasi');
    Route::get('/report/tag-sumber-dana', [ReportController::class, 'reportTagSumberDana']);
    Route::get('/report/kode-rekening', [ReportController::class, 'reportKodeRekening']);



    // APIS AKUNTANSI START -------------->>>>>>>>
    Route::get('/accountancy/lra', [LRAController::class, 'getLRA']);
    Route::post('/accountancy/lra', [LRAController::class, 'postLRA']);
    Route::post('/accountancy/lra/reset', [LRAController::class, 'resetLRA']);

    Route::post('/accountancy/saldo-awal', [AdminOnlyController::class, 'postSaldoAwal']);


    // Rekonsiliasi Aset Start
    Route::get('/accountancy/rekon-aset/rekap-belanja', [RekonsiliasiAsetController::class, 'getRekapBelanja']);
    Route::post('/accountancy/rekon-aset/rekap-belanja', [RekonsiliasiAsetController::class, 'saveRekapBelanja']);

    Route::get('/accountancy/rekon-aset/kib_a', [RekonsiliasiAsetController::class, 'getKibA']);
    Route::post('/accountancy/rekon-aset/kib_a', [RekonsiliasiAsetController::class, 'saveKibA']);
    Route::get('/accountancy/rekon-aset/kib_b', [RekonsiliasiAsetController::class, 'getKibB']);
    Route::post('/accountancy/rekon-aset/kib_b', [RekonsiliasiAsetController::class, 'saveKibB']);
    Route::get('/accountancy/rekon-aset/kib_c', [RekonsiliasiAsetController::class, 'getKibC']);
    Route::post('/accountancy/rekon-aset/kib_c', [RekonsiliasiAsetController::class, 'saveKibC']);
    Route::get('/accountancy/rekon-aset/kib_d', [RekonsiliasiAsetController::class, 'getKibD']);
    Route::post('/accountancy/rekon-aset/kib_d', [RekonsiliasiAsetController::class, 'saveKibD']);
    Route::get('/accountancy/rekon-aset/kib_e', [RekonsiliasiAsetController::class, 'getKibE']);
    Route::post('/accountancy/rekon-aset/kib_e', [RekonsiliasiAsetController::class, 'saveKibE']);
    Route::get('/accountancy/rekon-aset/aset-lain-lain', [RekonsiliasiAsetController::class, 'getAsetLainLain']);
    Route::post('/accountancy/rekon-aset/aset-lain-lain', [RekonsiliasiAsetController::class, 'saveAsetLainLain']);
    Route::get('/accountancy/rekon-aset/kdp', [RekonsiliasiAsetController::class, 'getKDP']);
    Route::post('/accountancy/rekon-aset/kdp', [RekonsiliasiAsetController::class, 'saveKDP']);
    Route::get('/accountancy/rekon-aset/aset-tak-berwujud', [RekonsiliasiAsetController::class, 'getAsetTakBerwujud']);
    Route::post('/accountancy/rekon-aset/aset-tak-berwujud', [RekonsiliasiAsetController::class, 'saveAsetTakBerwujud']);
    Route::get('/accountancy/rekon-aset/rekap-aset-lainnya', [RekonsiliasiAsetController::class, 'getRekapAsetLainnya']);
    Route::post('/accountancy/rekon-aset/rekap-aset-lainnya', [RekonsiliasiAsetController::class, 'saveRekapAsetLainnya']);
    Route::get('/accountancy/rekon-aset/penyusutan', [RekonsiliasiAsetController::class, 'getPenyusutan']);
    Route::post('/accountancy/rekon-aset/penyusutan', [RekonsiliasiAsetController::class, 'savePenyusutan']);

    Route::get('/accountancy/rekon-aset/rekap-aset-tetap', [RekonsiliasiAsetController::class, 'getRekapAsetTetap']);

    Route::get('/accountancy/rekon-aset/rekap-opd', [RekonsiliasiAsetController::class, 'getRekapOPD']);
    Route::get('/accountancy/rekon-aset/rekap', [RekonsiliasiAsetController::class, 'getRekap']);
    // Rekonsiliasi Aset End


    // Penyesuaian Aset dan Beban Start
    Route::get('/accountancy/padb/1', [PenyesuaianAsetDanBebanController::class, 'getPenyesuaianBebanBarjas']);
    Route::post('/accountancy/padb/1', [PenyesuaianAsetDanBebanController::class, 'storePenyesuaianBebanBarjas']);
    Route::delete('/accountancy/padb/1', [PenyesuaianAsetDanBebanController::class, 'deletePenyesuaianBebanBarjas']);

    Route::get('/accountancy/padb/2', [PenyesuaianAsetDanBebanController::class, 'getModalKeBeban']);
    Route::post('/accountancy/padb/2', [PenyesuaianAsetDanBebanController::class, 'storeModalKeBeban']);
    Route::delete('/accountancy/padb/2', [PenyesuaianAsetDanBebanController::class, 'deleteModalKeBeban']);

    Route::get('/accountancy/padb/3', [PenyesuaianAsetDanBebanController::class, 'getBarjasKeAset']);
    Route::post('/accountancy/padb/3', [PenyesuaianAsetDanBebanController::class, 'storeBarjasKeAset']);
    Route::delete('/accountancy/padb/3', [PenyesuaianAsetDanBebanController::class, 'deleteBarjasKeAset']);

    Route::get('/accountancy/padb/4', [PenyesuaianAsetDanBebanController::class, 'getPenyesuaianAset']);
    Route::post('/accountancy/padb/4', [PenyesuaianAsetDanBebanController::class, 'storePenyesuaianAset']);
    Route::delete('/accountancy/padb/4', [PenyesuaianAsetDanBebanController::class, 'deletePenyesuaianAset']);

    Route::get('/accountancy/padb/5', [PenyesuaianAsetDanBebanController::class, 'getAtribusi']);
    Route::post('/accountancy/padb/5', [PenyesuaianAsetDanBebanController::class, 'storeAtribusi']);
    Route::delete('/accountancy/padb/5', [PenyesuaianAsetDanBebanController::class, 'deleteAtribusi']);

    Route::get('/accountancy/padb/6.1', [PenyesuaianAsetDanBebanController::class, 'getMutasiAset']);
    Route::post('/accountancy/padb/6.1', [PenyesuaianAsetDanBebanController::class, 'storeMutasiAset']);
    Route::delete('/accountancy/padb/6.1', [PenyesuaianAsetDanBebanController::class, 'deleteMutasiAset']);

    Route::get('/accountancy/padb/6.2', [PenyesuaianAsetDanBebanController::class, 'getDaftarPekerjaan']);
    Route::post('/accountancy/padb/6.2', [PenyesuaianAsetDanBebanController::class, 'storeDaftarPekerjaan']);
    Route::delete('/accountancy/padb/6.2', [PenyesuaianAsetDanBebanController::class, 'deleteDaftarPekerjaan']);

    Route::get('/accountancy/padb/6.3', [PenyesuaianAsetDanBebanController::class, 'getHibahMasuk']);
    Route::post('/accountancy/padb/6.3', [PenyesuaianAsetDanBebanController::class, 'storeHibahMasuk']);
    Route::delete('/accountancy/padb/6.3', [PenyesuaianAsetDanBebanController::class, 'deleteHibahMasuk']);

    Route::get('/accountancy/padb/6.4', [PenyesuaianAsetDanBebanController::class, 'getHibahKeluar']);
    Route::post('/accountancy/padb/6.4', [PenyesuaianAsetDanBebanController::class, 'storeHibahKeluar']);
    Route::delete('/accountancy/padb/6.4', [PenyesuaianAsetDanBebanController::class, 'deleteHibahKeluar']);

    Route::get('/accountancy/padb/7', [PenyesuaianAsetDanBebanController::class, 'getPenilaianAset']);
    Route::post('/accountancy/padb/7', [PenyesuaianAsetDanBebanController::class, 'storePenilaianAset']);
    Route::delete('/accountancy/padb/7', [PenyesuaianAsetDanBebanController::class, 'deletePenilaianAset']);

    Route::get('/accountancy/padb/8', [PenyesuaianAsetDanBebanController::class, 'getPenghapusanAset']);
    Route::post('/accountancy/padb/8', [PenyesuaianAsetDanBebanController::class, 'storePenghapusanAset']);
    Route::delete('/accountancy/padb/8', [PenyesuaianAsetDanBebanController::class, 'deletePenghapusanAset']);

    Route::get('/accountancy/padb/9', [PenyesuaianAsetDanBebanController::class, 'getPenjualanAset']);
    Route::post('/accountancy/padb/9', [PenyesuaianAsetDanBebanController::class, 'storePenjualanAset']);
    Route::delete('/accountancy/padb/9', [PenyesuaianAsetDanBebanController::class, 'deletePenjualanAset']);
    // Penyesuaian Aset dan Beban Start

    // Belanja Bayar Dimuka Start
    Route::get('/accountancy/bbdm', [BelanjaBayarDimukaController::class, 'getBelanjaBayarDimuka']);
    Route::post('/accountancy/bbdm', [BelanjaBayarDimukaController::class, 'storeBelanjaBayarDimuka']);
    Route::delete('/accountancy/bbdm', [BelanjaBayarDimukaController::class, 'deleteBelanjaBayarDimuka']);
    // Belanja Bayar Dimuka End

    // Persediaan Start
    Route::get('/accountancy/persediaan/rekap', [PersediaanController::class, 'getRekap']);

    Route::get('/accountancy/persediaan/1', [PersediaanController::class, 'getBarangHabisPakai']);
    Route::post('/accountancy/persediaan/1', [PersediaanController::class, 'storeBarangHabisPakai']);
    Route::delete('/accountancy/persediaan/1', [PersediaanController::class, 'deleteBarangHabisPakai']);

    Route::get('/accountancy/persediaan/2', [PersediaanController::class, 'getBelanjaPersediaan']);
    Route::post('/accountancy/persediaan/2', [PersediaanController::class, 'storeBelanjaPersediaan']);
    Route::delete('/accountancy/persediaan/2', [PersediaanController::class, 'deleteBelanjaPersediaan']);

    Route::get('/accountancy/persediaan/3', [PersediaanController::class, 'getNilaiPersediaanNeraca']);
    Route::post('/accountancy/persediaan/3', [PersediaanController::class, 'storeNilaiPersediaanNeraca']);
    Route::delete('/accountancy/persediaan/3', [PersediaanController::class, 'deleteNilaiPersediaanNeraca']);
    // Persediaan End

    // Hutang Belanja Start
    Route::get('/accountancy/hutang-belanja', [HutangBelanjaController::class, 'getIndex']);
    // Hutang Belanja End


    // APIS AKUNTANSI END -------------->>>>>>>>
});

// Internal KOMINFO
Route::prefix('/local')->group(function () {
    Route::get('/dashboard/chart-realisasi', [App\Http\Controllers\API\Local\DashboardController::class, 'chartRealisasi'])->name('dashboard.realisasi.chart');
    // Route::get('/dashboard/summary-realisasi', [App\Http\Controllers\API\Local\DashboardController::class, 'summaryRealisasi'])->name('dashboard.realisasi.summary');
    Route::get('/dashboard/chart-kinerja', [App\Http\Controllers\API\Local\DashboardController::class, 'chartKinerja'])->name('dashboard.kinerja.chart');
    // Route::get('/dashboard/summary-kinerja', [App\Http\Controllers\API\Local\DashboardController::class, 'summaryKinerja'])->name('dashboard.kinerja.summary');
    Route::get('/dashboard/rank-instance', [App\Http\Controllers\API\Local\DashboardController::class, 'rankInstance'])->name('dashboard.rank-instance');
    Route::get('/dashboard/instance/{instance_id}', [App\Http\Controllers\API\Local\DashboardController::class, 'detailInstance'])->name('dashboard.instance.detail');
    Route::get('/dashboard/instance/{instance_id}/detail/prg/{id}', [App\Http\Controllers\API\Local\DashboardController::class, 'detailProgramInstance'])->name('dashboard.instance.detailPrg');
    Route::get('/dashboard/instance/{instance_id}/detail/kgt/{id}', [App\Http\Controllers\API\Local\DashboardController::class, 'detailKegiatanInstance'])->name('dashboard.instance.detailKgt');
    Route::get('/dashboard/instance/{instance_id}/detail/skgt/{id}', [App\Http\Controllers\API\Local\DashboardController::class, 'detailSubKegiatanInstance'])->name('dashboard.instance.detailSubKgt');


    Route::get('/caram/realisasi/listInstance', [BaseController::class, 'localListInstance'])->name('caram-realisasi.listInstance');
});

// Route::post('import/kode-rekening', [ImportController::class, 'importKodeRekening'])->name('import.kode-rekening');
