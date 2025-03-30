import React from 'react'

 export default function Footer () {

  const yearNow=new Date().getFullYear()

  return (
    <footer className=' w-full bg-color_gray_500'>
      <section className='py-8 pt-6 text-base font-normal border-t border-gray-800'>
        <p className='text-center text-gray-400'>© {yearNow} StreamVibe. Todos os direitos Reservados</p>
      </section>
    </footer>
  )
}
