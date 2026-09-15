<?php

namespace App\Http\Responses;

use Laravel\Fortify\Http\Responses\TwoFactorLoginResponse as FortifyTwoFactorLoginResponse;

class TwoFactorLoginResponse extends FortifyTwoFactorLoginResponse
{
    public function toResponse($request)
    {
        if (! $request->wantsJson() && $request->user()?->hasAnyRole(['Cliente', 'Institucion'])) {
            $request->session()->forget('url.intended');

            return redirect()->route('admin.solicitudes.index');
        }

        return parent::toResponse($request);
    }
}
