<?php

use App\Http\Controllers\API\AuthenticateController;
use App\Http\Controllers\API\BaseController;
use App\Http\Controllers\API\ImportController;
use App\Http\Controllers\API\MasterCaramController;
use App\Http\Controllers\API\RealisasiController;
use App\Http\Controllers\API\TestingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/testing', [TestingController::class, 'index']);

Route::post('/bdsm', [AuthenticateController::class, 'serverCheck']);
// Login
Route::post('/login', [AuthenticateController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
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

    // Instances Resources
    Route::get('instances', [BaseController::class, 'listInstance'])->name('instances.list');
    Route::post('instances', [BaseController::class, 'createInstance'])->name('instances.create');
    Route::get('instances/{id}', [BaseController::class, 'detailInstance'])->name('instances.detail');
    Route::post('instances/{id}', [BaseController::class, 'updateInstance'])->name('instances.update');
    Route::delete('instances/{id}', [BaseController::class, 'deleteInstance'])->name('instances.delete');

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

    // Master Ref Indikator Sub Kegiatan Resources
    Route::get('ref-indikator-sub-kegiatan', [MasterCaramController::class, 'listRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.list');
    Route::post('ref-indikator-sub-kegiatan', [MasterCaramController::class, 'createRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.create');
    Route::get('ref-indikator-sub-kegiatan/{id}', [MasterCaramController::class, 'detailRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.detail');
    Route::post('ref-indikator-sub-kegiatan/{id}', [MasterCaramController::class, 'updateRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.update');
    Route::delete('ref-indikator-sub-kegiatan/{id}', [MasterCaramController::class, 'deleteRefIndikatorSubKegiatan'])->name('ref-indikator-sub-kegiatan.delete');

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

    // Caram Realisasi Program Resources
    Route::get('caram/realisasi/listInstance', [RealisasiController::class, 'listInstance'])->name('caram-realisasi.listInstance');
    Route::get('caram/realisasi/listProgramsSubKegiatan', [RealisasiController::class, 'listProgramsSubKegiatan'])->name('caram-realisasi.listProgramsSubKegiatan');

    // Caram Realisasi Sub Kegiatan Resources
    Route::get('caram/realisasi/getDataSubKegiatan/{id}', [RealisasiController::class, 'getDataSubKegiatan'])->name('caram-realisasi.getDataSubKegiatan');

    // Caram Get Kode Rekening
    Route::get('caram/getKodeRekening', [RealisasiController::class, 'getKodeRekening'])->name('caram.getKodeRekening');

    // Caram Realisasi Get Data
    Route::get('caram/realisasi/getDataRealisasi/{id}', [RealisasiController::class, 'getDataRealisasi'])->name('caram-realisasi.getDataRealisasi');

    // Caram Realisasi Save Data
    Route::post('caram/realisasi/saveDataSubKegiatan', [RealisasiController::class, 'saveDataSubKegiatan'])->name('caram-realisasi.saveDataSubKegiatan');

    // Caram Detail Realisasi Data
    Route::get('caram/realisasi/detailDataSubKegiatan/{id}', [RealisasiController::class, 'detailDataSubKegiatan'])->name('caram-realisasi.detailDataSubKegiatan');

    // Caram Realisasi Update Data
    Route::post('caram/realisasi/updateDataSubKegiatan/{id}', [RealisasiController::class, 'updateDataSubKegiatan'])->name('caram-realisasi.updateDataSubKegiatan');

    // Caram Realisasi Delete Data
    Route::delete('caram/realisasi/deleteDataSubKegiatan/{id}', [RealisasiController::class, 'deleteDataSubKegiatan'])->name('caram-realisasi.deleteDataSubKegiatan');
});

Route::post('import/kode-rekening', [ImportController::class, 'importKodeRekening'])->name('import.kode-rekening');
