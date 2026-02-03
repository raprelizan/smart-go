<h1>Installer</h1>
<form method="post" action="/install">
    @csrf
    <label>Name <input type="text" name="name" /></label>
    <label>Email <input type="email" name="email" /></label>
    <label>Password <input type="password" name="password" /></label>
    <label>Confirm <input type="password" name="password_confirmation" /></label>
    <button type="submit">Install</button>
</form>
