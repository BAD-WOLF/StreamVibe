import React from 'react'

const Input = ({...props}) => {
  return (
    <>
       <input {...props} className='w-full py-2 px-4'/>
    </>
  )
}

export default Input
