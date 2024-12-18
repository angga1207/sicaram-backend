<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>
        {{ $info['title'] ?? '-' }}
    </title>

    <style>
        /*
        Import the desired font from Google fonts.
        */
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');

        /*
        Define all colors used in this template
        */
        :root {
            --font-color: black;
            --highlight-color: #03798e;
            --header-bg-color: #B8E6F1;
            --footer-bg-color: #BFC0C3;
            --table-row-separator-color: #BFC0C3;
        }

        @page {
            /*
          This CSS highlights how page sizes, margins, and margin boxes are set.
          https://docraptor.com/documentation/article/1067959-size-dimensions-orientation

          Within the page margin boxes content from running elements is used instead of a
          standard content string. The name which is passed in the element() function can
          be found in the CSS code below in a position property and is defined there by
          the running() function.
          */
            size: A4;
            margin: 0cm 0 0cm 0;

            @top-left {
                content: element(header);
            }

            @bottom-left {
                content: element(footer);
            }
        }

        /*
        The body itself has no margin but a padding top & bottom 1cm and left & right 2cm.
        Additionally the default font family, size and color for the document is defined
        here.
        */
        body {
            margin: 0;
            padding: 10px 20px;
            color: black;
            font-family: 'Montserrat', sans-serif;
            font-size: 10pt;
        }

        /*
        The links in the document should not be highlighted by an different color and underline
        instead we use the color value inherit to get the current texts color.
        */
        a {
            color: inherit;
            text-decoration: none;
        }

        /*
        For the dividers in the document we use an HR element with a margin top and bottom
        of 1cm, no height and only a border top of one millimeter.
        */
        hr {
            margin: 1cm 0;
            height: 0;
            border: 0;
            /* border-top: 1mm solid #60D0E4; */
            border-top: 1mm solid black;
        }

        /*
        The page header in our document uses the HTML HEADER element, we define a height
        of 8cm matching the margin top of the page (see @page rule) and a padding left
        and right of 2cm. We did not give the page itself a margin of 2cm to ensure that
        the background color goes to the edges of the document.

        As mentioned above in the comment for the @page the position property with the
        value running(header) makes this HTML element float into the top left page margin
        box. This page margin box repeats on every page in case we would have a multi-page
        invoice.
        */
        header {
            padding: 20px;
            position: running(header);
            /* background-color: #B8E6F1; */
        }

        /*
        For the different sections in the header we use some flexbox and keep space between
        with the justify-content property.
        */
        header .headerSection {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header .headerSection #title {
            font-size: 1.5em;
        }

        /*
        To move the first sections a little down and have more space between the top of
        the document and the logo/company name we give the section a padding top of 5mm.
        */
        header .headerSection:first-child {
            padding-top: 5px;
        }

        /*
        Similar we keep some space at the bottom of the header with the padding-bottom
        property.
        */
        header .headerSection:last-child {
            padding-bottom: 5px;
        }

        /*
        Within the header sections we have defined two DIV elements, and the last one in
        each headerSection element should only take 35% of the headers width.
        */
        header .headerSection div:last-child {
            /* width: 35%; */
        }

        /*
        For the logo, where we use an SVG image and the company text we also use flexbox
        to align them correctly.
        */
        header .logoAndName {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header .logoAndName img {
            /* width: 100%; */
            height: 1.5cm;
        }

        header .logo-container {
            text-align: center;
        }


        header .headerSection .invoiceDetails {
            padding-top: 5px;
        }

        /*
        The H3 element "ISSUED TO" gets another 25mm margin to the right to keep some
        space between this header and the client's address.
        Additionally this header text gets the hightlight color as font color.
        */
        header .headerSection h3 {
            /* margin: 0 .75cm 0 0; */
            /* color: #03798e; */
        }

        /*
        Put some margin between the "DUE DATE" and "AMOUNT" headings.
        */
        header .headerSection div:last-of-type h3:last-of-type {
            /* margin-top: .5cm; */
        }

        /*
        The paragraphs within the header sections DIV elements get a small 2px margin top
        to ensure its in line with the "ISSUED TO" header text.
        */
        header .headerSection div p {
            /* margin-top: 2px; */
        }

        /*
        All header elements and paragraphs within the HTML HEADER tag get a margin of 0.
        */
        header h1,
        header h2,
        header h3,
        header p {
            margin: 0;
        }

        /*
        The invoice details should not be uppercase and also be aligned to the right.
        */
        header .invoiceDetails,
        header .invoiceDetails h2 {
            text-align: right;
            font-size: 1em;
            text-transform: none;
        }

        /*
        Heading of level 2 and 3 ("DUE DATE", "AMOUNT" and "INVOICE TO") need to be written in
        uppercase, so we use the text-transform property for that.
        */
        header h2,
        header h3 {
            text-transform: uppercase;
        }

        /*
        The divider in the HEADER element gets a slightly different margin than the
        standard dividers.
        */
        header hr {
            margin: 10px 0 5px 0;
        }

        /*
        Our main content is all within the HTML MAIN element. In this template this are
        two tables. The one which lists all items and the table which shows us the
        subtotal, tax and total amount.

        Both tables get the full width and collapse the border.
        */
        header table {
            margin-top: 5px;
            width: 100%;
            border-collapse: collapse;
        }

        header table thead th {
            height: 1cm;
            border: 1px solid black;
        }

        header table tbody td {
            padding: 5px;
            text-align: start;
            border: 1px solid black;
        }

        header table tbody td:nth-of-type(2),
        header table tbody td:nth-of-type(3),
        header table tbody td:last-of-type {
            padding: 5px;
            text-align: center;
        }

        main h4 {
            font-weight: bold;
            font-size: 1.5em;
            /* color: #03798e; */
            text-align: center;
            text-transform: uppercase;
        }

        main .panel {
            padding-bottom: 30px;
            border-bottom: 2px solid #505050;
        }

        main .panel h5 {
            font-weight: bold;
            font-size: 1.2em;
            text-transform: uppercase;
        }

        main table {
            width: 100%;
            border-collapse: collapse;
        }

        /*
        We put the first tables headers in a THEAD element, this way they repeat on the
        next page if our table overflows to multiple pages.

        The text color gets set to the highlight color.
        */
        main table thead th {
            color: #fff;
            border: 1px solid black;
            padding: 10px;
            background: #505050;
        }

        /*
        For the last three columns we set a fixed width of 2.5cm, so if we would change
        the documents size only the first column with the item name and description grows.
        */
        /* main table thead th:nth-of-type(2),
        main table thead th:nth-of-type(3),
        main table thead th:last-of-type {
            width: 2.5cm;
        } */

        /*
        The items itself are all with the TBODY element, each cell gets a padding top
        and bottom of 2mm.
        */
        main table tbody td {
            padding: 10px;
            border: 1px solid black;
        }

        main table tbody td span.currency {
            white-space: nowrap;
        }

        main table.summary {
            float: right;
            margin-top: 20px;
            width: auto;
        }

        main table.summary tr.total {
            font-weight: bold;
            /* background-color: #60D0E4; */
            background-color: #505050;
            color: white !important;
        }

        main table.summary th {
            padding: 10px;
            width: 150px;
        }

        main table.summary td {
            padding: 10px 20px;
            border-bottom: 0;
            min-width: 150px;
        }
    </style>
</head>

<body>
    <header>
        <div class="headerSection">
            <div class="logoAndName">
                <div class="logo-container">
                    <img src="https://sicaramapis.oganilirkab.go.id/storage/images/pd/default.png" />
                    <img src="https://sicaram-dev.oganilirkab.go.id/assets/images/logo-caram.png" />
                </div>
                <h1 style="text-align: center">
                    {{ $info['title'] ?? ':title:' }}
                </h1>
            </div>
        </div>

        <hr />
        <div class="headerSection">
            <!-- The clients details come on the left side below the logo and company name. -->
            <div>
                <h3>
                    {{ $data['fullcode'] ?? ':fullcode:' }}
                </h3>
                <p>
                    <b id="title">
                        {{ $data['name'] ?? ':name:' }}
                    </b>
                    <br />
                    <br />
                    {{ $data['instance_code'] ?? ':kodeperangkatdaerah:' }}
                    <br>
                    {{ $data['instance_name'] ?? ':perangkatdaerah:' }}
                    <br />
                    <br />
                </p>
            </div>
            <!-- Additional details can be placed below the invoice details. -->
            <div>

                <h3>
                    Anggaran Renstra
                </h3>
                <p>
                    <b>
                        {{ isset($data['anggaranRenstra']) ? 'Rp. '.number_format($data['anggaranRenstra'], 0,'.','.') :
                        ':anggaranrenstra:' }}
                    </b>
                </p>
                <br>

                <h3>
                    Anggaran Renstra Perubahan
                </h3>
                <p>
                    <b>
                        {{ isset($data['anggaranRenja']) ? 'Rp. '.number_format($data['anggaranRenja'], 0,'.','.') :
                        ':anggaranrenja:' }}
                    </b>
                </p>
                <br>

                <h3>
                    Anggaran APBD
                </h3>
                <p>
                    <b>
                        {{ isset($data['anggaranApbd']) ? 'Rp. '.number_format($data['anggaranApbd'], 0,'.','.') :
                        ':anggaranapbd:' }}
                    </b>
                </p>
                <br>

                @if(isset($data['tagSumberDana']) && count($data['tagsSumberDana']) > 0)
                <h3>
                    Sumber Dana
                </h3>
                @foreach($data['tagsSumberDana'] as $sumberDana)
                <p>
                    <b>
                        {{ $sumberDana['name'] ?? ':sumberdana:' }}
                    </b>
                </p>
                @endforeach
                <br>
                @endif

                <h3>
                    Target Kinerja
                </h3>
                <div class="">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    Indikator Kinerja
                                </th>

                                @if($data['type'] != 'program')
                                <th>
                                    Target Kinerja Renstra
                                </th>
                                <th>
                                    Target Kinerja Renstra Perubahan
                                </th>
                                @else
                                <th>
                                    Target Kinerja RPJMD
                                </th>
                                @endif
                            </tr>
                        </thead>

                        <tbody>
                            @if(isset($data['targetKinerja']))
                            @foreach($data['targetKinerja'] as $targetKinerja)
                            <tr>
                                <td>
                                    {{ $targetKinerja['name'] ?? ':name:' }}
                                </td>
                                @if($data['type'] != 'program')
                                <td>
                                    {{
                                    ($targetKinerja['targetRenstra'] && $targetKinerja['satuanRenstra'])
                                    ? $targetKinerja['targetRenstra'] .' '. $targetKinerja['satuanRenstra']
                                    : ':targetrenstra:'
                                    }}
                                </td>
                                <td>
                                    {{
                                    ($targetKinerja['targetRenja'] && $targetKinerja['satuanRenja'])
                                    ? $targetKinerja['targetRenja'] .' '. $targetKinerja['satuanRenja']
                                    : ':targetrenja:'
                                    }}
                                </td>
                                @else
                                <td>
                                    {{
                                    ($targetKinerja['targetRpjmd'] && $targetKinerja['satuanRpjmd'])
                                    ? $targetKinerja['targetRpjmd'] .' '. $targetKinerja['satuanRpjmd']
                                    : ':targetrpjmd:'
                                    }}
                                </td>
                                @endif
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </header>

    <main>
        <h4>
            Tabel Realisasi
        </h4>

        @if(isset($info['triwulan']) && in_array($info['triwulan'], [0,1]))
        <div class="panel">
            <h5>
                Triwulan I
            </h5>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">
                            Indikator
                        </th>
                        <th rowspan="1" colspan="2">
                            Januari
                        </th>
                        <th rowspan="1" colspan="2">
                            Februari
                        </th>
                        <th rowspan="1" colspan="2">
                            Maret
                        </th>
                    </tr>
                    <tr>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                    </tr>
                </thead>
                <!-- The single invoice items are all within the TBODY of the table. -->
                <tbody>
                    @foreach($data['realisasi'] as $realisasi)
                    <tr>
                        <td>
                            <b>
                                {{ $realisasi['name'] ?? ':indikator:' }}
                            </b>
                        </td>
                        <td>
                            {{ number_format($realisasi[1]['kinerja'],2,',','.') }}
                            {{ $realisasi[1]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[1]['keuangan'] ? 'Rp. '. number_format($realisasi[1]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[2]['kinerja'],2,',','.') }}
                            {{ $realisasi[2]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[2]['keuangan'] ? 'Rp. '. number_format($realisasi[2]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[3]['kinerja'],2,',','.') }}
                            {{ $realisasi[3]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[3]['keuangan'] ? 'Rp. '. number_format($realisasi[3]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        @endif

        @if(isset($info['triwulan']) && in_array($info['triwulan'], [0,2]))
        <div class="panel">
            <h5>
                Triwulan II
            </h5>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">
                            Indikator
                        </th>
                        <th rowspan="1" colspan="2">
                            April
                        </th>
                        <th rowspan="1" colspan="2">
                            Mei
                        </th>
                        <th rowspan="1" colspan="2">
                            Juni
                        </th>
                    </tr>
                    <tr>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                    </tr>
                </thead>
                <!-- The single invoice items are all within the TBODY of the table. -->
                <tbody>
                    @foreach($data['realisasi'] as $realisasi)
                    <tr>
                        <td>
                            <b>
                                {{ $realisasi['name'] ?? ':indikator:' }}
                            </b>
                        </td>
                        <td>
                            {{ number_format($realisasi[4]['kinerja'],2,',','.') }}
                            {{ $realisasi[4]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[4]['keuangan'] ? 'Rp. '. number_format($realisasi[4]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[5]['kinerja'],2,',','.') }}
                            {{ $realisasi[5]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[5]['keuangan'] ? 'Rp. '. number_format($realisasi[5]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[6]['kinerja'],2,',','.') }}
                            {{ $realisasi[6]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[6]['keuangan'] ? 'Rp. '. number_format($realisasi[6]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        @endif

        @if(isset($info['triwulan']) && in_array($info['triwulan'], [0,3]))
        <div class="panel">
            <h5>
                Triwulan III
            </h5>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">
                            Indikator
                        </th>
                        <th rowspan="1" colspan="2">
                            Juli
                        </th>
                        <th rowspan="1" colspan="2">
                            Agustus
                        </th>
                        <th rowspan="1" colspan="2">
                            September
                        </th>
                    </tr>
                    <tr>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                    </tr>
                </thead>
                <!-- The single invoice items are all within the TBODY of the table. -->
                <tbody>
                    @foreach($data['realisasi'] as $realisasi)
                    <tr>
                        <td>
                            <b>
                                {{ $realisasi['name'] ?? ':indikator:' }}
                            </b>
                        </td>
                        <td>
                            {{ number_format($realisasi[7]['kinerja'],2,',','.') }}
                            {{ $realisasi[7]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[7]['keuangan'] ? 'Rp. '. number_format($realisasi[7]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[8]['kinerja'],2,',','.') }}
                            {{ $realisasi[8]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[8]['keuangan'] ? 'Rp. '. number_format($realisasi[8]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[9]['kinerja'],2,',','.') }}
                            {{ $realisasi[9]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[9]['keuangan'] ? 'Rp. '. number_format($realisasi[9]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        @endif

        @if(isset($info['triwulan']) && in_array($info['triwulan'], [0,4]))
        <div class="panel">
            <h5>
                Triwulan IV
            </h5>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">
                            Indikator
                        </th>
                        <th rowspan="1" colspan="2">
                            Oktober
                        </th>
                        <th rowspan="1" colspan="2">
                            November
                        </th>
                        <th rowspan="1" colspan="2">
                            Desember
                        </th>
                    </tr>
                    <tr>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                        <th>
                            Kinerja
                        </th>
                        <th>
                            Keuangan
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['realisasi'] as $realisasi)
                    <tr>
                        <td>
                            <b>
                                {{ $realisasi['name'] ?? ':indikator:' }}
                            </b>
                        </td>
                        <td>
                            {{ number_format($realisasi[10]['kinerja'],2,',','.') }}
                            {{ $realisasi[10]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[10]['keuangan'] ? 'Rp. '. number_format($realisasi[10]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[11]['kinerja'],2,',','.') }}
                            {{ $realisasi[11]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[11]['keuangan'] ? 'Rp. '. number_format($realisasi[11]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                        <td>
                            {{ number_format($realisasi[12]['kinerja'],2,',','.') }}
                            {{ $realisasi[12]['kinerjaSatuan'] }}
                        </td>
                        <td>
                            <span class="currency">
                                {{ $realisasi[12]['keuangan'] ? 'Rp. '. number_format($realisasi[12]['keuangan'],
                                0,'.','.') :
                                'Rp. 0' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
        @endif

        <!-- The summary table contains the subtotal, tax and total amount. -->
        <table class="summary">
            <tr>
                <th>
                    Anggaran APBD
                </th>
                <td>
                    {{ isset($data['anggaranApbd'])
                    ? 'Rp. '. number_format($data['anggaranApbd'],0,'.','.')
                    : 'Rp.0' }}
                </td>
            </tr>
            <tr>
                <th>
                    Realisasi Keuangan
                </th>
                <td>
                    {{ isset($data['total_realisasi_keuangan'])
                    ? 'Rp. '. number_format($data['total_realisasi_keuangan'],0,'.','.')
                    : 'Rp.0' }}
                </td>
            </tr>
            <tr class="total">
                <th>
                    Persentase Realisasi Keuangan
                </th>
                <td>
                    @if(isset($data['persentase_realisasi_keuangan']))
                    {{ number_format($data['persentase_realisasi_keuangan'],2,',',',') ?? '0' }} %
                    @endif
                </td>
            </tr>
        </table>

    </main>

</body>

</html>
