import React from 'react'

//import the component
import logo from '../../../../images/logo.png'

const Logo=()=>{
  return(
    <>
     <a className='' href='/'>
        <img src={logo} alt="Carregando logo" style={{width:"180px"}}/>
    </a>
    </>
  )
}

export default Logo