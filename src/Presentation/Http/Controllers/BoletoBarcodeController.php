<?php

namespace Kaninstein\MultiAcquirerCheckout\Presentation\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kaninstein\MultiAcquirerCheckout\Support\Boleto\BoletoLine;
use Picqer\Barcode\BarcodeGeneratorSVG;

final class BoletoBarcodeController
{
    public function __invoke(Request $request): Response
    {
        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return response('Missing boleto code', 422);
        }

        $digits = BoletoLine::toBarcodeDigits($code);

        if (! is_string($digits)) {
            return response('Invalid boleto code', 422);
        }

        $generator = new BarcodeGeneratorSVG();

        // Boleto barcode uses Interleaved 2 of 5 (ITF) with 44 digits.
        $svg = $generator->getBarcode($digits, $generator::TYPE_INTERLEAVED_2_5, 2, 60);

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
