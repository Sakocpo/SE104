<!DOCTYPE html>
<html lang = en>

<head>
    <meta charset = "UTF-8">
    <meta name = "viewport" content = "width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel = "stylesheet" href = "style.css">

<body>
    
    <div class="container">
        <div class="form-box active" id="login-form">
            <form action="login_register.php" method="post">
                <h2>Login</h2>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn" name="Login">Login</button>
                <p><a href="#" onclick="showForm('register-form')">Register?</a></p>
            </form>
        </div>
    </div>

    <div class="form-box" id="register-form">
        <form action="login_register.php" method="post">  
            <h2>Register</h2y
            >
            <input type="text" name="name" placeholder="Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="sdt" name="sdt" placeholder="SDT">
            <input type="password" name="password" placeholder="Password" required>
            <input type="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            <select name="role" required>
                <option >--Select Role--</option>
                <option value="Waiter">Waiter</option>
                <option value="Admin">Admin</option>
            </select>
            <button type="submit" class="btn" name="Register">Register</button>
            <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
        </form>
    </div>

    <script src="script.js"></script>
</body>



</html>