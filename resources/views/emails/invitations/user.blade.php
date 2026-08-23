<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Invitación a INNOVATEP Ideas</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; color: #172033; font-family: Arial, Helvetica, sans-serif;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; color: transparent;">
        Tienes una invitación para unirte al banco institucional de ideas de INFOTEP.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f9;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 620px; background-color: #ffffff; border-radius: 18px; overflow: hidden;">
                    <tr>
                        <td style="background-color: #003e6f; padding: 28px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="vertical-align: middle;">
                                        <div style="font-size: 24px; line-height: 1; font-weight: 800; letter-spacing: -0.5px; color: #ffffff;">INNOVATEP</div>
                                        <div style="margin-top: 7px; font-size: 11px; line-height: 1.4; letter-spacing: 1.5px; text-transform: uppercase; color: #d7e9f7;">Ideas · INFOTEP</div>
                                    </td>
                                    <td align="right" style="vertical-align: middle;">
                                        <div style="display: inline-block; padding: 10px 12px; border-radius: 12px; background-color: #005696; color: #ffffff; font-size: 22px; line-height: 1;">✦</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px 40px 16px;">
                            <div style="font-size: 13px; line-height: 1.5; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #005696;">Invitación de acceso</div>
                            <h1 style="margin: 12px 0 0; font-size: 30px; line-height: 1.2; letter-spacing: -0.6px; color: #003e6f;">Hola, {{ $invitation->name }}</h1>
                            <p style="margin: 18px 0 0; font-size: 16px; line-height: 1.65; color: #475569;">Has sido invitado(a) a formar parte de <strong style="color: #003e6f;">INNOVATEP Ideas</strong>, el espacio institucional para proponer, descubrir y transformar ideas de innovación en INFOTEP.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 12px 40px 8px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f8f9ff; border: 1px solid #dbe7f0; border-radius: 14px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <div style="font-size: 11px; line-height: 1.4; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #64748b;">Tu acceso institucional</div>
                                        <div style="margin-top: 10px; font-size: 14px; line-height: 1.6; color: #172033;"><strong>Correo:</strong> {{ $invitation->email }}</div>
                                        <div style="margin-top: 4px; font-size: 14px; line-height: 1.6; color: #172033;"><strong>Perfil:</strong> {{ $invitation->role === 'admin' ? 'Administrador' : 'Colaborador' }}</div>
                                        @if($invitation->regional)
                                            <div style="margin-top: 4px; font-size: 14px; line-height: 1.6; color: #172033;"><strong>Regional:</strong> {{ $invitation->regional->full_name }}</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 28px 40px 18px;">
                            <a href="{{ $invitationUrl }}" style="display: inline-block; padding: 15px 28px; border-radius: 10px; background-color: #feb700; color: #231f20; font-size: 15px; line-height: 1.2; font-weight: 800; text-decoration: none;">Activar mi cuenta</a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px 36px;">
                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #64748b; text-align: center;">Este enlace estará disponible durante 72 horas. Si no esperabas esta invitación, puedes ignorar este mensaje.</p>
                            <p style="margin: 18px 0 0; font-size: 12px; line-height: 1.6; color: #94a3b8; word-break: break-all; text-align: center;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br><a href="{{ $invitationUrl }}" style="color: #005696;">{{ $invitationUrl }}</a></p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 40px; background-color: #f8f9ff; border-top: 1px solid #e5edf3;">
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748b; text-align: center;">INNOVATEP Ideas · INFOTEP</p>
                            <p style="margin: 5px 0 0; font-size: 11px; line-height: 1.5; color: #94a3b8; text-align: center;">Banco institucional de ideas e innovación</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
