import { io } from "socket.io-client";

const SOCKET_URL = import.meta.env.VITE_SOCKET_URL || "http://localhost:3001";

// identifiant stable client (localStorage)
let clientId = localStorage.getItem("clientId");
if (!clientId) {
  clientId = crypto.randomUUID();
  localStorage.setItem("clientId", clientId);
}

export const socket = io(SOCKET_URL, {
  auth: { clientId },
  autoConnect: true,
});
