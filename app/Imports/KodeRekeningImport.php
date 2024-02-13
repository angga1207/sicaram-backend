<?php

namespace App\Imports;

use App\Models\Ref\KodeRekening1;
use App\Models\Ref\KodeRekening2;
use App\Models\Ref\KodeRekening3;
use App\Models\Ref\KodeRekening4;
use App\Models\Ref\KodeRekening5;
use App\Models\Ref\KodeRekening6;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class KodeRekeningImport implements ToCollection
{
    public function collection(Collection $collection)
    {
        $datas = collect($collection)->skip(1);
        foreach ($datas as $key => $item) {
            $kode_1 = $item[0];
            $kode_2 = $item[1];
            $kode_3 = $item[2];
            $kode_4 = $item[3];
            $kode_5 = $item[4];
            $kode_6 = $item[5];
            $fullcode = $item[6];
            $name = $item[7];

            // Kode Rekening 1 Start
            if (
                $kode_6 == null
                && $kode_5 == null
                && $kode_4 == null
                && $kode_3 == null
                && $kode_2 == null
                && $kode_1 != null
                && $fullcode != null
            ) {
                $data = new KodeRekening1();
                $data->periode_id = 1;
                $data->code = $kode_1;
                $data->fullcode = $fullcode;
                $data->name = $name;
                $data->status = 'active';
                $data->created_by = 1;
                $data->save();
            }
            // Kode Rekening 1 End

            // Kode Rekening 2 Start
            if (
                $kode_6 == null
                && $kode_5 == null
                && $kode_4 == null
                && $kode_3 == null
                && $kode_2 != null
                && $kode_1 != null
                && $fullcode != null
            ) {
                $data = new KodeRekening2();
                $data->ref_kode_rekening_1 = KodeRekening1::where('code', $kode_1)->first()->id;
                $data->periode_id = 1;
                $data->code = $kode_2;
                $data->fullcode = $fullcode;
                $data->name = $name;
                $data->status = 'active';
                $data->created_by = 1;
                $data->save();
            }
            // Kode Rekening 2 End

            // Kode Rekening 3 Start
            if (
                $kode_6 == null
                && $kode_5 == null
                && $kode_4 == null
                && $kode_3 != null
                && $kode_2 != null
                && $kode_1 != null
                && $fullcode != null
            ) {
                $rek1 = KodeRekening1::where('code', $kode_1)->first();
                $rek2 = KodeRekening2::where('code', $kode_2)->where('ref_kode_rekening_1', $rek1->id)->first();
                $data = new KodeRekening3();
                $data->ref_kode_rekening_1 = $rek1->id;
                $data->ref_kode_rekening_2 = $rek2->id;
                $data->periode_id = 1;
                $data->code = $kode_3;
                $data->fullcode = $fullcode;
                $data->name = $name;
                $data->status = 'active';
                $data->created_by = 1;
                $data->save();
            }
            // Kode Rekening 3 End

            // Kode Rekening 4 Start
            if (
                $kode_6 == null
                && $kode_5 == null
                && $kode_4 != null
                && $kode_3 != null
                && $kode_2 != null
                && $kode_1 != null
                && $fullcode != null
            ) {
                $rek1 = KodeRekening1::where('code', $kode_1)->first();
                $rek2 = KodeRekening2::where('code', $kode_2)->where('ref_kode_rekening_1', $rek1->id)->first();
                $rek3 = KodeRekening3::where('code', $kode_3)->where('ref_kode_rekening_2', $rek2->id)->first();

                $data = new KodeRekening4();
                $data->ref_kode_rekening_1 = $rek1->id;
                $data->ref_kode_rekening_2 = $rek2->id;
                $data->ref_kode_rekening_3 = $rek3->id;
                $data->periode_id = 1;
                $data->code = $kode_4;
                $data->fullcode = $fullcode;
                $data->name = $name;
                $data->status = 'active';
                $data->created_by = 1;
                $data->save();
            }
            // Kode Rekening 4 End

            // Kode Rekening 5 Start
            if (
                $kode_6 == null
                && $kode_5 != null
                && $kode_4 != null
                && $kode_3 != null
                && $kode_2 != null
                && $kode_1 != null
                && $fullcode != null
            ) {
                $rek1 = KodeRekening1::where('code', $kode_1)->first();
                $rek2 = KodeRekening2::where('code', $kode_2)->where('ref_kode_rekening_1', $rek1->id)->first();
                $rek3 = KodeRekening3::where('code', $kode_3)->where('ref_kode_rekening_2', $rek2->id)->first();
                $rek4 = KodeRekening4::where('code', $kode_4)->where('ref_kode_rekening_3', $rek3->id)->first();

                $data = new KodeRekening5();
                $data->ref_kode_rekening_1 = $rek1->id;
                $data->ref_kode_rekening_2 = $rek2->id;
                $data->ref_kode_rekening_3 = $rek3->id;
                $data->ref_kode_rekening_4 = $rek4->id;
                $data->periode_id = 1;
                $data->code = $kode_5;
                $data->fullcode = $fullcode;
                $data->name = $name;
                $data->status = 'active';
                $data->created_by = 1;
                $data->save();
            }
            // Kode Rekening 5 End

            // Kode Rekening 6 Start
            if (
                $kode_6 != null
                && $kode_5 != null
                && $kode_4 != null
                && $kode_3 != null
                && $kode_2 != null
                && $kode_1 != null
                && $fullcode != null
            ) {
                $rek1 = KodeRekening1::where('code', $kode_1)->first();
                $rek2 = KodeRekening2::where('code', $kode_2)->where('ref_kode_rekening_1', $rek1->id)->first();
                $rek3 = KodeRekening3::where('code', $kode_3)->where('ref_kode_rekening_2', $rek2->id)->first();
                $rek4 = KodeRekening4::where('code', $kode_4)->where('ref_kode_rekening_3', $rek3->id)->first();
                $rek5 = KodeRekening5::where('code', $kode_5)->where('ref_kode_rekening_4', $rek4->id)->first();

                $data = new KodeRekening6();
                $data->ref_kode_rekening_1 = $rek1->id;
                $data->ref_kode_rekening_2 = $rek2->id;
                $data->ref_kode_rekening_3 = $rek3->id;
                $data->ref_kode_rekening_4 = $rek4->id;
                $data->ref_kode_rekening_5 = $rek5->id;
                $data->periode_id = 1;
                $data->code = $kode_6;
                $data->fullcode = $fullcode;
                $data->name = $name;
                $data->status = 'active';
                $data->created_by = 1;
                $data->save();
            }
        }
    }
}
