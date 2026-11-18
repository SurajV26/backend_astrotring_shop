<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Adgebra Partner Login | AstroTring</title>
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 15% 10%,#342b55 0,#17172a 42%,#0b1020 100%);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:#182033}.login{width:min(100%,430px);background:#fff;border:1px solid #e8eaf0;border-radius:22px;padding:36px;box-shadow:0 28px 80px #0005}.mark{width:46px;height:46px;border-radius:14px;background:#f4e8ca;color:#8d621f;display:grid;place-items:center;font-weight:900;font-size:20px}.eyebrow{margin:24px 0 8px;color:#9a7437;text-transform:uppercase;letter-spacing:.16em;font-size:11px;font-weight:800}h1{margin:0;font-size:27px;letter-spacing:-.04em}p{color:#667085;font-size:14px;line-height:1.6;margin:10px 0 24px}label{display:block;margin:16px 0 7px;font-size:13px;font-weight:700;color:#344054}input{width:100%;height:48px;border:1px solid #d8dce5;border-radius:10px;padding:0 14px;font-size:15px;outline:none}input:focus{border-color:#bc8d3b;box-shadow:0 0 0 3px #bc8d3b22}button{margin-top:22px;width:100%;height:49px;border:0;border-radius:10px;background:#182033;color:#fff;font-weight:750;font-size:14px;cursor:pointer}button:hover{background:#263552}.error{margin-top:14px;padding:11px 13px;border-radius:9px;background:#fff1f1;color:#b42318;font-size:13px}.foot{margin-top:22px;text-align:center;color:#98a2b3;font-size:11px}
    </style>
</head>
<body>
<main class="login">
    <div class="mark">A</div>
    <div class="eyebrow">AstroTring Partner Portal</div>
    <h1>Adgebra sign in</h1>
    <p>Sign in to view only the AstroTring customers, saved carts and orders attributed to Adgebra landing-page traffic.</p>
    <form method="POST" action="{{ route('adgebra.login.submit') }}">
        @csrf
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus maxlength="80">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
            <div class="error" style="background:#ecfdf3;color:#027a48">{{ session('status') }}</div>
        @endif
        <button type="submit">Sign in securely</button>
    </form>
    <div class="foot">Restricted partner access · AstroTring</div>
</main>
</body>
</html>
