
<!DOCTYPE html>
<html>

<body>
  <h1 style="text-align: center">Fitness With Dina</h1>
  <h5>Hello {{$mail_data['name']}}</h5>,
  <p>Welcome to Fitness With Dina, your guide to be perfect in health.</p>
  <p>Your Email Verfification Email Code is <strong>{{$mail_data['verification_code']}}</strong>.</p>
  <p>Please do not share this code with anyone. The code will expire in 5 minutes</p>
  <p>Thank you</p>
</body>
</html>