<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pulse Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 20px;
            text-align: center;
        }

        h3 {
            margin-top: 0;
            color: #4b5563;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat {
            font-size: 2rem;
            font-weight: bold;
            color: #111827;
        }

        .label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <h1>Pulse Dashboard (CI4)</h1>

    <div class="grid">
        <!-- System Stats -->
        <div class="card">
            <h3>System Health</h3>
            <div class="mb-4">
                <div class="label">CPU Usage</div>
                <div class="stat"><span id="cpu-val">--</span>%</div>
            </div>
            <div>
                <div class="label">Memory Usage</div>
                <div class="stat"><span id="mem-val">--</span></div>
            </div>
            <div>
                <div class="label">Memory Available</div>
                <div class="stat"><span id="mem-stat">--</span></div>
            </div>
        </div>

        <!-- Database -->
        <div class="card">
            <h3>Database</h3>
            <div class="label">Connection</div>
            <div class="stat" style="font-size: 1.25rem" id="db-conn">--</div>
        </div>

        <!-- Usage -->
        <div class="card">
            <h3>Status</h3>
            <div class="label">Last Updated</div>
            <div id="last-updated">Waiting...</div>
        </div>

        <!-- Request Stats -->
        <div class="card">
            <h3>Avg Response Time (1h)</h3>
            <div class="stat"><span id="avg-req">--</span> ms</div>
        </div>

        <!-- Slow Requests List -->
        <div class="card" style="grid-column: 1 / -1;">
            <h3>Slow Requests (> 1s)</h3>
            <table style="width: 100%; text-align: left; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 8px;">Time</th>
                        <th style="padding: 8px;">Path</th>
                        <th style="padding: 8px;">Count</th>
                        <th style="padding: 8px;">Slowest Duration</th>
                    </tr>
                </thead>
                <tbody id="slow-req-list">
                    <tr>
                        <td colspan="4" style="padding: 8px;">No slow requests found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Slow Queries List -->
        <div class="card" style="grid-column: 1 / -1;">
            <h3>Slow Queries (> 50ms)</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 8px; width: 100px;">Time</th>
                            <th style="padding: 8px;">SQL</th>
                            <th style="padding: 8px;">Count</th>
                            <th style="padding: 8px; width: 100px;">Slowest Duration</th>
                        </tr>
                    </thead>
                    <tbody id="slow-query-list">
                        <tr>
                            <td colspan="4" style="padding: 8px;">No slow queries found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Exceptions List -->
        <div class="card" style="grid-column: 1 / -1;">
            <h3>Recent Exceptions</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 8px; width: 100px;">Time</th>
                            <th style="padding: 8px;">Message</th>
                            <th style="padding: 8px; width: 150px;">Location</th>
                        </tr>
                    </thead>
                    <tbody id="exception-list">
                        <tr>
                            <td colspan="3" style="padding: 8px;">No exceptions recorded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function fetchStats() {
            try {
                // 1. Trigger check (normally done via cron, but we trigger it here for demo sweetness)
                await fetch('/pulse/check');

                // 2. Fetch data
                const res = await fetch('/pulse/stats');
                const data = await res.json();

                // Update DOM
                document.getElementById('cpu-val').innerText = data.cpu || 0;
                document.getElementById('mem-val').innerText = formatBytes(data.memory || 0);
                document.getElementById('mem-stat').innerText = formatBytes(data.avail_mem || 0) + ' / ' + formatBytes(data.total_mem || 0);
                document.getElementById('db-conn').innerText = data.db || 'Unknown';
                document.getElementById('avg-req').innerText = data.avg_req_time || 0;

                const now = new Date();
                document.getElementById('last-updated').innerText = now.toLocaleTimeString();

                // Update Slow Requests Table
                const tbody = document.getElementById('slow-req-list');
                if (data.slow_requests && data.slow_requests.length > 0) {
                    tbody.innerHTML = data.slow_requests.map(req => `
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 8px; color: #6b7280;">${req.time}</td>
                            <td style="padding: 8px; font-family: monospace;">${req.path}</td>
                            <td style="padding: 8px; font-family: monospace;">${req.count}</td>
                            <td style="padding: 8px; color: #ef4444; font-weight: bold;">${req.duration}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" style="padding: 8px;">No slow requests found.</td></tr>';
                }

                // Update Slow Queries Table
                const tbodyQuery = document.getElementById('slow-query-list');
                if (data.slow_queries && data.slow_queries.length > 0) {
                    tbodyQuery.innerHTML = data.slow_queries.map(q => `
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 8px; color: #6b7280;">${q.time}</td>
                            <td style="padding: 8px; font-family: monospace; font-size: 0.85em; word-break: break-all;">${q.sql}</td>
                            <td style="padding: 8px; font-family: monospace; font-size: 0.85em; word-break: break-all;">${q.count}</td>
                            <td style="padding: 8px; color: #ef4444; font-weight: bold;">${q.duration}</td>
                        </tr>
                    `).join('');
                } else {
                    tbodyQuery.innerHTML = '<tr><td colspan="4" style="padding: 8px;">No slow queries found.</td></tr>';
                }

                // Update Exceptions Table
                const tbodyExc = document.getElementById('exception-list');
                if (data.exceptions && data.exceptions.length > 0) {
                    tbodyExc.innerHTML = data.exceptions.map(e => `
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 8px; color: #6b7280;">${e.time}</td>
                            <td style="padding: 8px; color: #ef4444; font-weight: bold; font-size: 0.9em;">${e.message}</td>
                            <td style="padding: 8px; color: #6b7280; font-size: 0.85em;">${e.location}</td>
                        </tr>
                    `).join('');
                } else {
                    tbodyExc.innerHTML = '<tr><td colspan="3" style="padding: 8px;">No exceptions recorded.</td></tr>';
                }

            } catch (err) {
                console.error(err);
            }
        }

        // Poll every 2 seconds
        setInterval(fetchStats, 2000);
        fetchStats();
    </script>
    <script>
        function formatBytes(bytes) {
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            if (bytes === 0) return 'n/a';
            const i = parseInt(String(Math.floor(Math.log(bytes) / Math.log(1024))));
            return Math.round(bytes / Math.pow(1024, i)) + ' ' + sizes[i];
        }

        function formatAvailableMem(bytes) {
            const sizes = ['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            if (bytes === 0) return 'n/a';
            const i = parseInt(String(Math.floor(Math.log(bytes) / Math.log(1024))));
            return Math.round(bytes / Math.pow(1024, i)) + ' ' + sizes[i];
        }
    </script>
</body>

</html>