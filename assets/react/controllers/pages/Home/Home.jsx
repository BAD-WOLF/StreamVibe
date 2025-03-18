import React from 'react'
import Banner from './Banner'
import Depoimento from './Depoimento'
import dennis from '../../../../images/users/dennis.jpg'
import goreth from '../../../../images/users/goreth.jpg'
import lau from '../../../../images/users/lau.jpg'
import maria from '../../../../images/users/maria.jpg'
import mathe from '../../../../images/users/mathe.jpg'
import varla from '../../../../images/users/varla.jpg'

export default function Home(){

    const testimonials = [
        { quote: "Uma experiência cinematográfica incrível! Cada filme e série  aqui me surpreenderam. Não perco mais tempo escolhendo o que assistir!", name: "Dennis Camela", designation: "Visitante", src:dennis },

        { quote: "Se você ama filmes e séries tanto quanto eu, esse site é perfeito! As análises e sugestões são sempre certeiras!", name: "Goreth Antonio", designation: "Atriz", src: goreth },

        { quote: "Uma experiência cinematográfica incrível! Cada filme e série  aqui me surpreenderam. Não perco mais tempo escolhendo o que assistir!", name: "Laumara Andre", designation: "Visitante", src:lau },

        { quote: "Se você ama filmes e séries tanto quanto eu, esse site é perfeito! As análises e sugestões são sempre certeiras!", name: "Maria Paulin", designation: "Atriz", src: maria },
        
        { quote: "Se você ama filmes e séries tanto quanto eu, esse site é perfeito! As análises e sugestões são sempre certeiras!", name: "Matheu Joao", designation: "Visitante", src: mathe },
        
        { quote: "Uma experiência cinematográfica incrível! Cada filme e série  aqui me surpreenderam. Não perco mais tempo escolhendo o que assistir!", name: "Carla Margarida", designation: "Visitante", src:varla },

      ]

     return (
        <>
        <main className='container text-gray-200 w-full h-lvh'>
            <section>
                <Banner/>
            </section>            

            {/* Show the films and serie */}
            <section className='py-4'>
                <Depoimento testimonials={testimonials} autoplay/>
            </section>
        </main>
        </>
     )
}