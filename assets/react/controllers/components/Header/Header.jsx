import React from 'react'
import {Link } from 'react-router'
import './Header.css'
import {FaUser} from 'react-icons/fa'

//import the component
import Logo from '../Logo/Logo'
import Button from '../Forms/Button'

export default function Header(){

    return(
        <>
        <main className={`bg-color_gray_500 sticky top-0`}>
            <header className={`flex justify-between items-center py-2 px-4`}>

                <section>
                    <Logo/>
                </section>

                <nav className={` text-gray-200 flex justify-start items-center gap-2 text-base `}>
                    <Link to='/' className={` font-medium hover:font-normal hover:text-gray-300 transition-all`}>Films</Link>
                    <Link to='/' className='font-medium hover:font-normal hover:text-gray-300 transition-all'>Series</Link>
                    <Link to='/' className='font-medium hover:font-normal hover:text-gray-300 transition-all'>Populares</Link>
                    <Link to='/' className='font-medium hover:font-normal hover:text-gray-300 transition-all'>Suporte</Link>
                    <Link to='/' className='font-medium hover:font-normal hover:text-gray-300 transition-all'>Contactos</Link>
                </nav>

                <section>
                <Button>
                    <span><FaUser/></span>
                    <span>Minha Conta</span>
                </Button>
                </section>

            </header>
        </main>
        </>
    )
}