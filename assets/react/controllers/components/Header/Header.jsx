import React from 'react'
import {FaUser} from 'react-icons/fa'

//import the component
import Logo from '../Logo/Logo'
import Button from '../Forms/Button'

export default function Header(){

    return(
        <>
        <main className={`sticky top-0 z-50 mainHeader backdrop-blur-md shadow-sm shadow-gray-800`}>
            <header className={`flex justify-between flex-wrap items-center py-3 px-4`}>

                <section>
                    <Logo/>
                </section>

                <nav className={` text-gray-200 flex justify-start items-center gap-2 text-base z-50 `}>
                    <a href='/movies' className={` font-light hover:font-normal hover:text-gray-300 transition-all`}>Movies</a>
                    <a href='/series' className='font-light hover:font-normal hover:text-gray-300 transition-all'>Series</a>
                    <a href='/support' className='font-light hover:font-normal hover:text-gray-300 transition-all'>Suporte</a>
                    <a href='/about' className='font-light hover:font-normal hover:text-gray-300 transition-all'>Sobre</a>
                </nav>

                <section className='hidden md:flex'>
                <Button>
                    <span><FaUser/></span>
                    <span>Entrar Agora</span>
                </Button>

                </section>

            </header>
        </main>
        </>
    )
}