<!DOCTYPE html>
<html>
<head>
    <title>验证码</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .code {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>验证码</h2>
        <p>您好：</p>
        <p>您正在进行身份验证，您的验证码为：</p>
        <div class="code">{{ $code }}</div>
        <p>该验证码有效期为5分钟，请及时使用。</p>
        <p>如非本人操作，请忽略此邮件。</p>
        <p>此致</p>
        <p>实验室借还系统</p>
    </div>
</body>
</html>