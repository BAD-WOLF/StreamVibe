import React from 'react';
import { FaStar, FaStarHalfAlt, FaRegStar } from "react-icons/fa";


export function renderStars(render) {
    const stars = [];
    const maxStars = 5;
    const fullStars = Math.floor(render / 2); // porque a nota é até 10
    const hasHalfStar = render % 2 >= 1;
  
    for (let i = 0; i < fullStars; i++) {
      stars.push(<FaStar key={i} className="text-yellow-400" />);
    }
  
    if (hasHalfStar && fullStars < maxStars) {
      stars.push(<FaStarHalfAlt key="half" className="text-yellow-400" />);
    }
  
    while (stars.length < maxStars) {
      stars.push(<FaRegStar key={stars.length} className="text-yellow-400" />);
    }
  
    return stars;
  }