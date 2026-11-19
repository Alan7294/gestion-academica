<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    /**
     * RF10: bandeja de avisos del usuario.
     */
    public function index(Request $request): View
    {
        return view('notificaciones.index', [
            'notificaciones' => $request->user()->notifications()->paginate(15),
        ]);
    }

    public function leer(Request $request, DatabaseNotification $notificacion): RedirectResponse
    {
        abort_unless(
            $notificacion->notifiable_id === $request->user()->id
            && $notificacion->notifiable_type === $request->user()::class,
            403,
        );

        $notificacion->markAsRead();

        return redirect($notificacion->data['url'] ?? route('notificaciones.index'));
    }

    public function leerTodas(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
