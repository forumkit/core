<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">

    <style>

      body {
        background: #fff;
        margin: 0;
        padding: 0;
        line-height: 1.5;
      }
      body, input, button {
        font-family: 'Open Sans', sans-serif;
        font-size: 16px;
        color: #7E96B3;
      }
      .container {
        max-width: 500px;
        margin: 0 auto;
        padding: 30px;
        text-align: center;
      }
      a {
        color: #10b981;
        text-decoration: none;
      }
      a:hover {
        text-decoration: underline;
      }

      form {
        margin-top: 40px;
      }
      .FormGroup {
        margin-bottom: 20px;
      }
      .FormGroup .FormField:first-child input {
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
      }
      .FormGroup .FormField:last-child input {
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
      }
      .FormField input {
        background: #E6F7EC;
        margin: 0 0 1px;
        border: 2px solid transparent;
        transition: background 0.2s, border-color 0.2s, color 0.2s;
        width: 100%;
        padding: 10px 10px 10px 180px;
        box-sizing: border-box;
      }
      .FormField input:focus {
        border-color: #10b981;
        background: #fff;
        color: #444;
        outline: none;
      }
      .FormField label {
        float: left;
        width: 160px;
        text-align: right;
        margin-right: -160px;
        position: relative;
        margin-top: 12px;
        font-size: 14px;
        pointer-events: none;
        opacity: 0.7;
      }
      button {
        background: #10b981;
        color: #fff;
        border: 0;
        font-weight: bold;
        border-radius: 4px;
        cursor: pointer;
        padding: 15px 30px;
        -webkit-appearance: none;
      }
      button[disabled] {
        opacity: 0.5;
      }

      #error {
        background: #D83E3E;
        color: #fff;
        padding: 15px 20px;
        border-radius: 4px;
        margin-bottom: 20px;
      }

      .Problems {
        margin-top: 50px;
      }
      .Problems .Problem:first-child {
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
      }
      .Problems .Problem:last-child {
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
      }
      .Problem {
        background: #EDF2F7;
        margin: 0 0 1px;
        padding: 20px 25px;
        text-align: left;
      }
      .Problem-message {
        font-size: 16px;
        color: #3C5675;
        font-weight: normal;
        margin: 0;
      }
      .Problem-detail {
        font-size: 13px;
        margin: 5px 0 0;
      }
    </style>
  </head>

  <body>
    <div class="container">
      <div>
        <?php echo $content; ?>
      </div>
    </div>
  </body>
</html>
