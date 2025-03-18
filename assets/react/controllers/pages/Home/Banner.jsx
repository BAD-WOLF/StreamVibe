import React from 'react'
import banner from '../../../../images/banner.jpg'
import {FaSearch} from 'react-icons/fa'

const Banner = () => {
  return (
    <section className="relative bg-cover bg-center h-72 z-10 flex flex-col justify-center items-center" 
            style={{ backgroundImage: `url(${banner})`}}>

            <div className={`bg-gray-950/50 h-full w-full absolute top-0 left-0 z-0`}></div>
            <div className="flex flex-col justify-center items-center z-10">
                <div className=" text-gray-200 flex flex-col gap-4">
                    <div>    
                        <h1 className=" text-3xl font-normal md:text-5xl md:font-medium">Seja Bem Vindo<span className='text-ciano_escuro'>,</span></h1>
                        <p className="text-xl md:text-2xl font-light">Aqui voce vai conhecer melhores Films, series e até mesmo Atores do momento </p>
                    </div>

                    <form className='relative flex justify-center items-center'>
                        <input type='search' placeholder='Pesquisar por films, series ou nome de um autor' className='w-full py-2 px-6 pr-28 text-lg text-gray-600 font-light placeholder:text-base placeholder:text-gray-400 placeholder:font-light rounded-full'/>
                        
                        <span className='absolute top-0 right-0'>
                             <button className=' bg-ciano_escuro px-4 py-2 rounded-e-full text-lg font-light'>Procurar</button>
                        </span>
                    </form>
                </div>
            </div>

    </section>
  )
}

export default Banner
