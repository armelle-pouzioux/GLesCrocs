require("dotenv").config();
const express = require("express");
const http = require("http");
const cors = require("cors");
const { Server } = require("socket.io");
const emitRoutes = require("./emitRoutes");

const app = express();
app.use(express.json());

app.use(cors({
  origin: process.env.CORS_ORIGIN || "http://localhost:5173",
  credentials: true
}));

app.get("/health", (_, res) => res.json({ ok: true }));

const server = http.createServer(app);

const io = new Server(server, {
  cors: {
    origin: process.env.CORS_ORIGIN || "http://localhost:5173",
    credentials: true
  }
});

io.on("connection", socket => {
  console.log("socket connected:", socket.id);
  socket.on("disconnect", () => {
    console.log("socket disconnected:", socket.id);
  });
});

app.use(emitRoutes(io));

const PORT = process.env.PORT || 3001;
server.listen(PORT, () => {
  console.log(`Socket server running on http://localhost:${PORT}`);
});
