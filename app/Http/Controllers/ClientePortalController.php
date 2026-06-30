<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientePortalController extends Controller
{
    public function vincularTelegram(Request $request): RedirectResponse
    {
        $request->validate([
            'telegram_chat_id' => 'required|string|max:50',
        ]);

        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return back()->with('error', 'Perfil de cliente no encontrado.');
        }

        DB::table('personas')
            ->where('id_persona', $cliente->id_persona)
            ->update(['telegram_chat_id' => $request->telegram_chat_id]);

        return back()->with('success', 'Telegram vinculado correctamente. Ya recibirás notificaciones.');
    }

    public function desvincularTelegram(): RedirectResponse
    {
        $cliente = Cliente::where('id_usuario', Auth::id())->first();

        if (!$cliente) {
            return back()->with('error', 'Perfil de cliente no encontrado.');
        }

        DB::table('personas')
            ->where('id_persona', $cliente->id_persona)
            ->update(['telegram_chat_id' => null]);

        return back()->with('success', 'Telegram desvinculado.');
    }
}
