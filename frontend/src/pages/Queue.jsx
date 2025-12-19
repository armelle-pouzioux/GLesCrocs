import { useEffect, useMemo, useState } from "react";
import { socket } from "../services/socket";
import { getOrdersQueue } from "../services/api";

function formatMinutes(seconds) {
  const mins = Math.max(0, Math.round(seconds / 60));
  if (mins <= 1) return "1 min";
  return `${mins} min`;
}

function statusLabel(status) {
  switch (status) {
    case "VALIDATED":
      return "Prise en compte";
    case "PREPARING":
      return "En préparation";
    case "PAID":
      return "Payée";
    case "READY":
      return "Prête";
    case "COMPLETED":
      return "Terminée";
    case "CANCELLED":
      return "Annulée";
    default:
      return status || "-";
  }
}

export default function Queue() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // (temp) mon ticket — plus tard on le settera à la création commande
  const [myTicket, setMyTicket] = useState(() => {
    const v = localStorage.getItem("myTicket");
    return v ? Number(v) : null;
  });

  async function refreshQueue() {
    setError("");
    try {
      const json = await getOrdersQueue();
      if (!json.success) {
        setError(json?.error?.message || "Erreur API");
        return;
      }
      setOrders(json.data.orders || []);
    } catch (e) {
      setError("Impossible de charger la file (API injoignable)");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    refreshQueue();

    // temps réel : au moindre changement, on resync via l'API
    const onQueueUpdated = () => refreshQueue();
    socket.on("queueUpdated", onQueueUpdated);

    // à la reconnexion, on resync aussi
    const onConnect = () => refreshQueue();
    socket.on("connect", onConnect);

    return () => {
      socket.off("queueUpdated", onQueueUpdated);
      socket.off("connect", onConnect);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const currentTicket = useMemo(() => {
    if (!orders.length) return null;
    // le premier ticket "non prêt" est le plus logique à afficher
    const active = orders.find((o) => o.status !== "READY");
    return active ? Number(active.ticket_number) : Number(orders[0].ticket_number);
  }, [orders]);

  const myOrder = useMemo(() => {
    if (!myTicket) return null;
    return orders.find((o) => Number(o.ticket_number) === Number(myTicket)) || null;
  }, [orders, myTicket]);

  const estimatedWaitSec = useMemo(() => {
    if (!myTicket || !orders.length) return null;

    // On somme les estimated_prep_sec des commandes devant moi,
    // en excluant celles déjà READY (préparation terminée)
    const ahead = orders.filter((o) => {
      const t = Number(o.ticket_number);
      return t < myTicket && o.status !== "READY";
    });

    const sum = ahead.reduce((acc, o) => acc + (Number(o.estimated_prep_sec) || 0), 0);
    return sum;
  }, [orders, myTicket]);

  function handleSetMyTicket() {
    const raw = prompt("Entre ton numéro de ticket (ex: 12) :");
    if (!raw) return;
    const t = Number(raw);
    if (!Number.isFinite(t) || t <= 0) return;
    localStorage.setItem("myTicket", String(t));
    setMyTicket(t);
  }

  function clearMyTicket() {
    localStorage.removeItem("myTicket");
    setMyTicket(null);
  }

  return (
    <div style={{ maxWidth: 720, margin: "0 auto", padding: 16 }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", gap: 12 }}>
        <div>
          <div style={{ fontSize: 14, opacity: 0.8 }}>Ticket en cours</div>
          <div style={{ fontSize: 28, fontWeight: 700 }}>
            {currentTicket ? `#${currentTicket}` : "-"}
          </div>
        </div>

        <div style={{ textAlign: "right" }}>
          <div style={{ fontSize: 14, opacity: 0.8 }}>Mon ticket</div>
          <div style={{ fontSize: 28, fontWeight: 700 }}>
            {myTicket ? `#${myTicket}` : "-"}
          </div>

          <div style={{ marginTop: 8, display: "flex", gap: 8, justifyContent: "flex-end" }}>
            {!myTicket ? (
              <button onClick={handleSetMyTicket}>Définir mon ticket</button>
            ) : (
              <button onClick={clearMyTicket}>Réinitialiser</button>
            )}
          </div>
        </div>
      </div>

      {myTicket && (
        <div style={{ marginTop: 16, padding: 12, border: "1px solid #ddd", borderRadius: 8 }}>
          <div style={{ fontWeight: 600 }}>
            Suivi de ma commande {myTicket ? `(#${myTicket})` : ""}
          </div>

          {myOrder ? (
            <div style={{ marginTop: 8 }}>
              <div>Status : {statusLabel(myOrder.status)}</div>
              {estimatedWaitSec !== null && myOrder.status !== "READY" && (
                <div style={{ marginTop: 4, opacity: 0.9 }}>
                  Temps estimé : {formatMinutes(estimatedWaitSec)}
                </div>
              )}
              {myOrder.status === "READY" && (
                <div style={{ marginTop: 6, fontWeight: 700 }}>
                  Ton repas est prêt.
                </div>
              )}
            </div>
          ) : (
            <div style={{ marginTop: 8, opacity: 0.85 }}>
              Ce ticket n’est pas (ou plus) dans la file.
            </div>
          )}
        </div>
      )}

      <div style={{ marginTop: 24 }}>
        <h2 style={{ margin: "0 0 8px 0" }}>File d’attente</h2>

        {loading && <div>Chargement…</div>}
        {error && <div style={{ color: "crimson" }}>{error}</div>}

        {!loading && !error && orders.length === 0 && (
          <div>Aucune commande pour le moment.</div>
        )}

        {!loading && !error && orders.length > 0 && (
          <ul style={{ listStyle: "none", padding: 0, margin: 0 }}>
            {orders.map((o) => {
              const isMine = myTicket && Number(o.ticket_number) === Number(myTicket);
              return (
                <li
                  key={o.id}
                  style={{
                    padding: 12,
                    border: "1px solid #e5e5e5",
                    borderRadius: 8,
                    marginBottom: 10,
                    background: isMine ? "rgba(0,0,0,0.04)" : "transparent",
                  }}
                >
                  <div style={{ display: "flex", justifyContent: "space-between", gap: 12 }}>
                    <div style={{ fontWeight: 700 }}>
                      Ticket #{o.ticket_number} {isMine ? "— (moi)" : ""}
                    </div>
                    <div style={{ opacity: 0.9 }}>{statusLabel(o.status)}</div>
                  </div>

                  <div style={{ marginTop: 6, fontSize: 13, opacity: 0.85 }}>
                    Estimation préparation: {formatMinutes(Number(o.estimated_prep_sec) || 0)}
                  </div>
                </li>
              );
            })}
          </ul>
        )}
      </div>
    </div>
  );
}
