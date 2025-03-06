import React from 'react'
import Link from 'react-router'

export default function Header(){
    return(
        <>
            <header className='bg-gray-800'>
                <section className='Logo'>
                    <Link>IMBD2</Link>
                </section>

                <nav className='nav'>
                <Link>Films</Link>
                <Link>Series</Link>
                <Link>Ajuda</Link>
                <Link>Suporte</Link>
                </nav>
            </header>
        </>
    )
}