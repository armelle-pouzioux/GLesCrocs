const express = require("express");
const { QUEUE_UPDATED, ORDER_READY } = require("./events");

const router = express.Router();

function requireToken(req, res, next) {
  const token = req.header("x-emit-token");
  if (!process.env.EMIT_TOKEN || token !== process.env.EMIT_TOKEN) {
    return res.status(401).json({ success: false });
  }
  next();
}

module.exports = function emitRoutes(io) {

  router.post("/emit/queue-updated", requireToken, (req, res) => {
    io.emit(QUEUE_UPDATED, { ts: Date.now() });
    res.json({ success: true });
  });

  router.post("/emit/order-ready", requireToken, (req, res) => {
    const { orderId, ticketNumber } = req.body || {};
    io.emit(ORDER_READY, { orderId, ticketNumber, ts: Date.now() });
    res.json({ success: true });
  });

  return router;
};
