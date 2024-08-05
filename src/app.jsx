import { useState } from 'react'
// import reactLogo from './assets/react.svg'
// import viteLogo from '/vite.svg'
import './app.css'
import { Button } from "flowbite-react";
import { Alert } from "flowbite-react";
import * as React from 'react';
import { createRoot } from 'react-dom/client';




function App() {
  // const [count, setCount] = useState(0)

  return (
    <>
      <div>
      sdfsdfdsf
      <Button color="blue">Blue</Button>

      </div>
      <h1>Vite + React</h1>
      
    </>
  )
}

const root = createRoot(document.getElementById('root'));
root.render(App());
