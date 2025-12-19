import Queue from "./pages/Queue.jsx";
import "./App.css";

function App() {
  return (
    <div className="app">
      <header className="app-header">
        <h1>La cantine — File d’attente</h1>
      </header>

      <main className="app-content">
        <Queue />
      </main>
    </div>
  );
}

export default App;
