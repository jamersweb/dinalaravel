<!DOCTYPE html>
<html lang="en">
<body>
    <h1 style="text-align: center">Fitness With Dina</h1>
    <h5>Hello {{$mail_data['name']}},</h5>
    <p>You have requested an email verification code. Your verification code is <strong>{{$mail_data['verification_code']}}</strong></p>
    <p>Do not share this code with anyone.</p>
    <p>If you didn't requested it you can ignore this email your account is safe if your email is secure.</p>
    <h5>Regards, Team FWD.</h5>
</body>
</html>