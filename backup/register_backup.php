<style>
<?php
include 'CSS/indexStyle.css';
?> 
</style>

<form action="fitbit_registration_page.php">

  <div class="container">


    <label for="uname"><b>Username</b></label>
    <input type="text" placeholder="Enter Username" name="uname" required>

    <label for="psw"><b>Password</b></label>
    <input type="password" placeholder="Enter Password" name="psw" required>

    <div class="clearfix">
     <button type="submit">Login</button>
     <button type="register">Register</button>   
    </div>
  </div>
  
</form>
