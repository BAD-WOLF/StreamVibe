import React, {Component} from 'react';

export default class extends Component {
    constructor(props) {

        super(props);

        this.state = {
            msg: props.name
        }

    }

    render() {
        return (<h1 className="flex justify-center items-center min-h-screen">Hello {this.state.msg}</h1>);
    }

}
