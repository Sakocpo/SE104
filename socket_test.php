<!DOCTYPE html>
<html>
<head><title>Socket Test</title></head>
<body>
  <script>
    const socket = new WebSocket("ws://localhost:8080");

    socket.onopen = () => {
      console.log("✅ Connected to WebSocket");

      // move this after onmessage is defined
      socket.send("Hello from browser");
      socket.send("gay");
    };

    socket.onmessage = (e) => {
      console.log("📨 Received:", e.data);
    };

    socket.onerror = (e) => {
      console.error("❌ Socket error", e);
    };

    socket.onclose = () => {
      console.log("🔌 Socket closed");
    };
  </script>
</body>
</html>
