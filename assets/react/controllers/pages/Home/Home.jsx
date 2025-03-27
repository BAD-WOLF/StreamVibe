import React from 'react'

// Import the components
import Banner from './Banner'
import Depoimento from './Depoimento'
import Header from '../../components/Header/Header'
import Footer from '../../components/Footer/Footer'

// import img for profile testimonials
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
        <Header/>
        <main className='text-gray-200 w-full min-h-screen'>

            {/* Banner */}
            <section>
                <Banner/>
            </section>            

            <section className='Container md:px-4 px-8'>

                {/* Description */}
                <section className='md:px-0 pt-8'>
                    <p className='md:text-center text-justify text-gray-400 text-base font-light'>A StreamVibe oferece uma experiência completa para amantes do cinema e séries, reunindo informações detalhadas sobre os títulos mais populares, lançamentos e clássicos do entretenimento. Com uma interface intuitiva, os usuários podem explorar sinopses, elencos, trailers e avaliações, facilitando a escolha do próximo filme ou série para assistir.</p>
                </section>

                {/* Show the testimonials */}
                <section className='flex-grow mb-16'>
                    <Depoimento testimonials={testimonials} autoplay/>
                </section>
                
            </section>
        </main>
        <Footer/>
        </>
     )
}