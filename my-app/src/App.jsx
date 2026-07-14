import './App.css';


function ClickButton(){
  alert('script is enqueued');
}
export default function App() {
  return (
    <button onClick={ClickButton()}>click</button>
  );
}

