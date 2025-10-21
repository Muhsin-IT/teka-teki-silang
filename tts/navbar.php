<style>
    /* Navigation Bar Styles */
    .navbar {
        background: #202b3c;
        /* Dark background for navbar */
        padding: 10px 20px;
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .navbar a {
        color: #f0f0f0;
        text-decoration: none;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 3px;
        transition: background 0.3s;
    }

    .navbar a:hover {
        background: #3c4a5c;
    }

    .navbar a.active {
        background: #4285f4;
        /* Google Blue for active link */
        color: #fff;
    }
</style>


<nav class="navbar">
    <a href="index.php">Layar Proyektor (Peserta)</a>
    <a href="admin.php" class="active">Admin Panel (Kontrol)</a>
    <a href="questions.php">Input/Atur Soal</a>
</nav>