
<!DOCTYPE html>
<html>
<head>
  <style>
    a{
      text-align: center;
      padding: 10px 20px;
      border: none;
      border-radius: 10px;
      background-color: rgb(230, 154, 154);
      color: white;
      font-weight: bold;
      margin-top: 20px;

    }
  </style>
</head>
  <body>
    <div>
        <h1 style="text-align-center">Hello {{$data['name']}}</h1>
        <h5>We have recieved a request to reset your password. Click on the link below to reset. </h5>
        <a target="_blank" href="{{url('')}}/cms/forgot-password?token={{ $data['token'] }}">Reset</a>
        <p style="margin-top:20px">The link will expire in 5 minutes. If you didn't requested this change, simply ignore.</p>
    </div>
</body>
</html>
