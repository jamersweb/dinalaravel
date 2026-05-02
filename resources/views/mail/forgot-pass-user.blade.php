
<!DOCTYPE html>
<html>
<head>
  <style>
  </style>
</head>
  <body>
    <div>
        <h1 style="text-align-center">Hello {{$data['name']}}</h1>
        <h5>We have recieved a request to reset your password. Here is code to verify its you: {{ $data['verification_code'] }}. </h5>
        <p style="margin-top:20px">If you didn't requested this change, simply ignore.</p>
    </div>
</body>
</html>
