import { motion } from "framer-motion";
import { useState } from "react";

export default function RippleButton() {
  const [ripples, setRipples] = useState([]);

  const handleClick = (e) => {
    const x = e.clientX - e.target.offsetLeft;
    const y = e.clientY - e.target.offsetTop;
    const newRipple = { x, y, id: Date.now() };
    setRipples([...ripples, newRipple]);
    
    setTimeout(() => {
      setRipples((prev) => prev.filter((ripple) => ripple.id !== newRipple.id));
    }, 600);
  };

  return (
    <div className="relative inline-block">
      <button
        onClick={handleClick}
        className="relative overflow-hidden px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg shadow-lg transition-transform transform hover:scale-105 active:scale-95"
      >
        Click Me
        {ripples.map(({ x, y, id }) => (
          <motion.span
            key={id}
            initial={{ opacity: 0.5, scale: 0 }}
            animate={{ opacity: 0, scale: 3 }}
            transition={{ duration: 0.6, ease: "ease-out" }}
            className="absolute bg-white opacity-30 rounded-full w-10 h-10"
            style={{ top: y - 20, left: x - 20 }}
          />
        ))}
      </button>
    </div>
  );
}
