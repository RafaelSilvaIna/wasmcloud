document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('error-message');
    
    try {
        const response = await fetch('api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            localStorage.setItem('sys_token', data.token);
            
            if (data.role === 'master') {
                window.location.href = 'views/admin/index.html';
            } else if (data.role === 'coordinator') {
                window.location.href = 'views/coordinator/index.html';
            } else {
                window.location.href = 'views/student/index.html';
            }
        } else {
            errorDiv.innerText = data.error || 'Falha na autenticação.';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.innerText = 'Falha de comunicação segura com o servidor.';
        errorDiv.style.display = 'block';
    }
});