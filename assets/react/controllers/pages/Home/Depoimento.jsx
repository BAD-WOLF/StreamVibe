import { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { MdKeyboardArrowRight,MdKeyboardArrowLeft  } from "react-icons/md";


const Depoimento = ({ testimonials, autoplay = false}) => {
  const [active, setActive] = useState(0);

  const handleNext = () => {
    setActive((prev) => (prev + 1) % testimonials.length);
  };

  const handlePrev = () => {
    setActive((prev) => (prev - 1 + testimonials.length) % testimonials.length);
  };

  useEffect(() => {
    if (autoplay) {
      const interval = setInterval(handleNext, 5000);
      return () => clearInterval(interval);
    }
  }, [autoplay]);

  return (
    <section className={`relative w-full py-8`}>
      <h2 className="text-2xl font-medium pb-4">Depoimentos</h2>

      <div className="relative grid grid-cols-1 md:grid-cols-2 md:gap-40 gap-14">

        <div className="relative h-80 w-full">

          <AnimatePresence>
            {testimonials.map((testimonial, index) => (
              <motion.div
              key={testimonial.src}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: active === index ? 1 : 0.7, scale: active === index ? 1 : 0.95 }}
              exit={{ opacity: 0, scale: 0.9 }}
              transition={{ duration: 0.4, ease: "easeInOut" }}
              className={`absolute inset-0 ${active === index ? 'z-10' : 'z-0'}`}
            >

              <img
                src={testimonial.src}
                alt={testimonial.name}
                width={500}
                height={500}
                className="h-full w-full rounded-2xl object-cover"
              />
            </motion.div>
            ))}
          </AnimatePresence>

        </div>

        <div className="flex flex-col justify-between py-4">
          <motion.div
            key={active}
            initial={{ y: 20, opacity: 0 }}
            animate={{ y: 0, opacity: 1 }}
            exit={{ y: -20, opacity: 0 }}
            transition={{ duration: 0.2, ease: "easeInOut" }}
          >
            <h3 className="text-xl font-medium">{testimonials[active].name}</h3>
            <p className="text-sm text-gray-400">{testimonials[active].designation}</p>
            <p className="text-sm text-gray-600 mt-5">{testimonials[active].quote}</p>
          </motion.div>

          <div className="flex gap-4 pt-8 md:pt-0">
            <button onClick={handlePrev} className="h-7 w-7 rounded-full bg-gray-200 flex items-center justify-center">
              <MdKeyboardArrowLeft className="h-5 w-5 text-gray-700" />
            </button>
            <button onClick={handleNext} className="h-7 w-7 rounded-full bg-gray-200 flex items-center justify-center">
              <MdKeyboardArrowRight className="h-5 w-5 text-gray-700" />
            </button>
          </div>
        </div>
      </div>
    </section>
  );
};

export default Depoimento