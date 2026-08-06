<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">

    <style>
        body {
            background-color: #f8fafc;
            color: #374151;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            line-height: 1.6;
        }

        a {
            color: #3b82f6;
            text-decoration: none;
        }

        a:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .wrapper {
            width: 100%;
            padding: 40px 20px;
            background-color: #f8fafc;
        }

        .content {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid #e5e7eb;
        }

        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.3;
        }

        .inner-body {
            width: 100%;
            padding: 40px 30px;
            background-color: #ffffff;
        }

        .content-cell h1 {
            color: #1f2937;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 20px;
            line-height: 1.3;
        }

        .content-cell h2 {
            color: #374151;
            font-size: 15px;
            font-weight: 600;
            margin: 25px 0 15px;
        }

        .content-cell p {
            color: #4b5563;
            font-size: 12px;
            line-height: 2;
            margin: 0 0 5px;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            padding: 30px 20px;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .footer a {
            color: #3b82f6;
        }

        .footer-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e5e7eb, transparent);
            margin: 15px 0;
        }

        .social-links {
            margin: 20px 0 10px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #6b7280;
            font-size: 12px;
            text-decoration: none;
        }

        /* Responsive Design */
        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 20px 10px !important;
            }
            
            .inner-body {
                padding: 25px 20px !important;
            }

            .header {
                padding: 25px 15px !important;
            }

            .header-title {
                font-size: 20px !important;
            }

            .content-cell h1 {
                font-size: 20px !important;
            }

            .footer {
                padding: 25px 15px !important;
            }
        }

        @media only screen and (max-width: 480px) {
            .content {
                border-radius: 8px !important;
            }
            
            .logo {
                height: 40px !important;
            }
        }
    </style>
</head>
<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <h1 style="color: #ffffff; margin: 0; font-size: 21px; text-align: center;">
                                LEMBAGA BAHASA
                            </h1>
                            <p style="color: #ffffff; margin: 0; font-size: 12px; text-align: center;">
                                Universitas Muhammadiyah Metro
                            </p>
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0">
                            <table class="inner-body" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        {!! Illuminate\Mail\Markdown::parse($slot) !!}
                                        
                                        <!-- Custom footer content -->
                                        <div style="margin-top: 30px; text-align: center; font-size: 5px; color: #6b7280;">
                                            <p>Jika memiliki pertanyaan, silakan hubungi kami di 
                                            <a href="mailto:info@lembagabahasa.site">info@lembagabahasa.site</a>
                                            </p>
                                            <p><em>Email ini dikirim secara otomatis, mohon tidak membalas email ini.</em></p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td>
                            <table class="footer" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell" align="center">
                                        <strong>Lembaga Bahasa UM Metro</strong><br>
                                        Jl. Ki Hajar Dewantara No.116, Metro Barat<br>
                                        Kota Metro, Lampung<br><br>
                                        
                                        <div class="footer-divider"></div>
                                        
                                        <div class="social-links">
                                            <a href="mailto:info@lembagabahasa.site">Email</a> |
                                            <a href="https://wa.me/6287790740408">WhatsApp</a> |
                                            <a href="{{ config('app.url') }}">Website</a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>