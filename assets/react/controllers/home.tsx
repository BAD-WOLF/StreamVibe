import { Component } from 'react';

// Definindo a interface para as props
interface Props {
    name?: string;
}

// Definindo a interface para o estado
interface State {
    msg?: string;
}

// Nomeando e tipando o componente
export default class extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = {
            msg: props.name,
        };
    }

    render() {
        return (
            <div className="flex justify-center items-center min-h-screen">
                <h1>Hello {this.state.msg}</h1>
            </div>
        );
    }
}
