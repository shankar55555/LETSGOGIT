<!DOCTYPE html>
<html>

<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- <link rel="stylesheet" type="text/css" href="{{ asset('css/email.css') }}" /> -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style type="text/css">
        @media screen {
            @font-face {
                font-family: "Lato";
                font-style: normal;
                font-weight: 400;
                src: local("Lato Regular"), local("Lato-Regular"),
                    url(https://fonts.gstatic.com/s/lato/v11/qIIYRU-oROkIk8vfvxw6QvesZW2xOQ-xsNqO47m55DA.woff) format("woff");
            }

            @font-face {
                font-family: "Lato";
                font-style: normal;
                font-weight: 700;
                src: local("Lato Bold"), local("Lato-Bold"),
                    url(https://fonts.gstatic.com/s/lato/v11/qdgUG4U09HnJwhYI-uK18wLUuEpTyoUstqEm5AMlJo4.woff) format("woff");
            }

            @font-face {
                font-family: "Lato";
                font-style: italic;
                font-weight: 400;
                src: local("Lato Italic"), local("Lato-Italic"),
                    url(https://fonts.gstatic.com/s/lato/v11/RYyZNoeFgb0l7W3Vu1aSWOvvDin1pK8aKteLpeZ5c0A.woff) format("woff");
            }

            @font-face {
                font-family: "Lato";
                font-style: italic;
                font-weight: 700;
                src: local("Lato Bold Italic"), local("Lato-BoldItalic"),
                    url(https://fonts.gstatic.com/s/lato/v11/HkF_qI1x_noxlxhrhMQYELO3LdcAZYWl9Si6vvxL-qU.woff) format("woff");
            }
        }

        /* CLIENT-SPECIFIC STYLES */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
        }

        /* RESET STYLES */
        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        /* iOS BLUE LINKS */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
            font-size: inherit !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
        }

        /* MOBILE STYLES */
        @media screen and (max-width: 600px) {
            h1 {
                font-size: 32px !important;
                line-height: 32px !important;
            }
        }

        /* ANDROID CENTER FIX */
        div[style*="margin: 16px 0;"] {
            margin: 0 !important;
        }

        .front-logo h2 {
            color: var(--darker);
            font-size: 36px;
            margin-bottom: 0;
            font-weight: bold;
        }

        .front-logo h2 span {
            color: #002e62 !important;
        }

        .email_background_color {
            background: rgb(var(--v-theme-primary)) !important;
        }
    </style>
</head>

<body style="background-color: #f4f4f4;margin: 0 !important;padding: 0 !important;overflow: hidden !important;">
    <!-- HIDDEN PRE HEADER TEXT -->
    <div style="display: none;font-size: 1px;color: #fefefe;line-height: 1px;font-family: 'Lato', Helvetica, Arial, sans-serif;max-height: 0px;max-width: 0px;opacity: 0;overflow: hidden;">
        {{$hidden_pre_header}}
    </div>
    <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" bgcolor="{{ $setting['email_color'] ?? '#7367f0' }}">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px">
                    <tr>
                        <td align="center" valign="top" style="padding: 40px 10px 40px 10px"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="{{ $setting['email_color'] ?? '#7367f0' }}" align="center" style="padding: 0px 10px 0px 10px">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px">
                    <tr>
                        <td bgcolor="#ffffff" align="center" valign="top" style="padding: 40px 20px 20px 20px;border-radius: 4px 4px 0px 0px;color: #111111;font-family: 'Lato', Helvetica, Arial, sans-serif;font-size: 48px;line-height: 48px;">
                            @if($setting['company_logo'])
                            <img alt="{{ config('app.name') }}"
                                style="border:0; line-height:100%; outline:none; text-decoration:none; border-width:0; width:165px; height:auto; padding-top:26px; padding-bottom:24px"
                                src="{{ $setting['company_logo'] ?? '' }}"
                                class="CToWUd"
                                width="165">
                            @else
                            <svg width="86" height="48" viewBox="0 0 34 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M0.00183571 0.3125V7.59485C0.00183571 7.59485 -0.141502 9.88783 2.10473 11.8288L14.5469 23.6837L21.0172 23.6005L19.9794 10.8126L17.5261 7.93369L9.81536 0.3125H0.00183571Z"
                                    style="fill: {{ $setting['email_color'] ?? '#7367f0' }}"></path>
                                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M8.17969 17.7762L13.3027 3.75173L17.589 8.02192L8.17969 17.7762Z" fill="#161616"></path>
                                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M8.58203 17.2248L14.8129 5.24231L17.6211 8.05247L8.58203 17.2248Z" fill="#161616"></path>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25781 17.6914L25.1339 0.3125H33.9991V7.62657C33.9991 7.62657 33.8144 10.0645 32.5743 11.3686L21.0179 23.6875H14.5487L8.25781 17.6914Z"
                                    style="fill: {{ $setting['email_color'] ?? '#7367f0' }}"></path>
                            </svg>
                            @endisset
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#f4f4f4" align="center" style="padding: 0px 10px 0px 10px">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px">
                    <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 20px 30px 40px 30px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 400; line-height: 25px; ">
                            <p>
                                {!!$content !!}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" align="left" style="padding: 0px 30px 40px 30px;border-radius: 0px 0px 4px 4px;color: #666666;font-family: 'Lato', Helvetica, Arial, sans-serif;font-size: 16px;font-weight: 400;line-height: 25px;">
                            <p style="margin: 0">Take care!<br />{{$setting['company_name'] ?? ''}}<br />{{$setting['address'] ?? ''}}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#f4f4f4" align="center" style="padding: 30px 10px 0px 10px">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px">
                    <tr>
                        <td bgcolor="{{ $setting['email_color'] ?? '#7367f0' }}" align="center"
                            style="padding: 30px 30px 30px 30px; border-radius: 4px; color: #666666; font-family: 'Lato', Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 400; line-height: 25px;">

                            <h2 style="font-size: 20px; font-weight: 400; color: #111111; margin: 0;">
                                Need more help?
                            </h2>

                            <p style="margin: 0">
                                <a href="{{ config('app.url') }}" target="_blank" style="color: #002e62;">We’re here to help you out</a>
                                <br />
                                or
                                <br />
                                <span style="color: #000000;">{{ $setting['phone'] ?? '' }}</span>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>
</body>

</html>
