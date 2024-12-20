<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>UIB Pusat Data Akreditasi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <a href="chat.php">
                <img alt="UIB Logo" height="50" src="https://www.uib.ac.id/wp-content/uploads/2024/01/Logo-Panjang-UIB.png" width="100" />
            </a>
            <div class="nav">
                <a href="https://www.uib.ac.id/">Beranda</a>
                <a href="https://www.uib.ac.id/tentang-uib/">Tentang UIB</a>
                <a href="https://www.uib.ac.id/kuliah-di-uib/">Fakultas</a>
                <a href="https://www.uib.ac.id/program-internasional/">Program Internasional</a>
            </div>
            <a class="new-chat-button" href="javascript:void(0);" onclick="newChat()">New Chat</a>
        </div>

        <div class="chat-container">
            <div class="chat-box" id="chat-box">
                <div class="chat-message bot-message">
                    <p>Hello! How can I assist you today?</p>
                </div>
            </div>
        </div>
        <div class="input-area">
            <input type="text" id="user-input" placeholder="Type your message..." onkeydown="checkEnter(event)">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function sendMessage() {
            const userInput = document.getElementById('user-input').value;
            if (userInput.trim() !== "") {
                const userMessage = document.createElement('div');
                userMessage.classList.add('chat-message', 'user-message');
                userMessage.innerHTML = `<p>${userInput}</p>`;
                document.querySelector('.chat-box').appendChild(userMessage);

                document.getElementById('user-input').value = "";
                sendToServer(userInput);
            }
        }

        function sendToServer(input) {
            const formData = new FormData();
            formData.append("question", input);

            fetch("process_request.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Fetch response not OK");
                    }
                    return response.json();
                })
                .then(data => {
                    const botMessage = document.createElement('div');
                    botMessage.classList.add('chat-message', 'bot-message');

                    if (data.error) {
                        botMessage.innerHTML = `<p>Error: ${data.error}</p>`;
                    } else if (data.data && data.data.length > 0) {
                        renderOutput(data.data, botMessage);
                    } else {
                        botMessage.innerHTML = `<p>Tidak ada data yang sesuai dengan pertanyaan.</p>`;
                    }

                    document.querySelector('.chat-box').appendChild(botMessage);
                    document.querySelector('.chat-container').scrollTop = document.querySelector('.chat-container').scrollHeight;
                })
                .catch(error => {
                    console.error("Error:", error);
                });
        }

        function renderOutput(data, container) {
            if (Array.isArray(data)) {
                // Render data sebagai tabel
                const table = document.createElement('table');
                table.classList.add('output-table');

                // Tambahkan header tabel
                const headers = Object.keys(data[0]);
                const headerRow = document.createElement('tr');
                headers.forEach(header => {
                    const th = document.createElement('th');
                    th.textContent = header;
                    headerRow.appendChild(th);
                });
                table.appendChild(headerRow);

                // Tambahkan data ke tabel
                data.forEach(row => {
                    const tr = document.createElement('tr');
                    headers.forEach(header => {
                        const td = document.createElement('td');
                        td.textContent = row[header];
                        tr.appendChild(td);
                    });
                    table.appendChild(tr);
                });

                container.appendChild(table);
            } else if (typeof data === 'object' && data.labels && data.values) {
                // Render data sebagai grafik menggunakan Chart.js
                const canvas = document.createElement('canvas');
                container.appendChild(canvas);

                new Chart(canvas, {
                    type: 'bar',  
                    data: {
                        labels: data.labels, // Menambahkan label untuk grafik
                        datasets: [{
                            label: data.chartLabel || 'Data Chart', // Menambahkan label chart
                            data: data.values, // Menambahkan nilai untuk grafik
                            backgroundColor: 'rgba(75, 192, 192, 0.2)', // Warna background bar
                            borderColor: 'rgba(75, 192, 192, 1)', // Warna border bar
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true // Mulai dari angka 0
                            }
                        }
                    }
                });
            } else {
                container.innerHTML = `<p>Data format tidak dikenali.</p>`;
            }
        }


        function checkEnter(event) {
            if (event.key === "Enter") {
                sendMessage();
            }
        }

        function newChat() {
            document.querySelector('.chat-box').innerHTML = '';
            const botMessage = document.createElement('div');
            botMessage.classList.add('chat-message', 'bot-message');
            botMessage.innerHTML = `<p>Hello! How can I assist you today?</p>`;
            document.querySelector('.chat-box').appendChild(botMessage);
            document.getElementById('user-input').value = '';
        }
    </script>

</body>

</html>