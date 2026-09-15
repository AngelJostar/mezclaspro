<?php

namespace App\Http\Responses;

use Laravel\Fortify\Http\Responses\LoginResponse as FortifyLoginResponse;

class LoginResponse extends FortifyLoginResponse
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
