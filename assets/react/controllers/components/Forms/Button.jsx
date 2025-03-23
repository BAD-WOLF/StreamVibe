import React from 'react'

const Button =({children})=>{
    return (
        <>
         <button className='flex justify-center items-center gap-1 text-gray-200 bg-ciano_escuro py-2 px-2 rounded-sm font-normal text-base'>{children}</button>
        </>
    )
}

export default Button