const API_URL = import.meta.env.VITE_API_URL || "http://localhost:8000";

export async function getOrdersQueue() {
  const res = await fetch(`${API_URL}/api/orders`, { credentials: "include" });
  return res.json();
}
