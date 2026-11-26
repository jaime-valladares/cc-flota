export default function filtroMes() {
    return {
        abierto: false,
        valor: '',
        maximo: '',
        anio: 0,
        meses: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],

        init() {
            const campo = this.$el.querySelector('input');
            this.maximo = campo.getAttribute('max');
            this.valor = /^\d{4}-(0[1-9]|1[0-2])$/.test(campo.value) ? campo.value : '';
        },

        get anioMaximo() {
            return Number(this.maximo.slice(0, 4));
        },

        get texto() {
            return this.valor ? this.valor.slice(5, 7) + '/' + this.valor.slice(0, 4) : 'MM/AAAA';
        },

        fecha(mes) {
            return String(this.anio).padStart(4, '0') + '-' + String(mes).padStart(2, '0');
        },

        esFuturo(mes) {
            return this.fecha(mes) > this.maximo;
        },

        alternar() {
            if (this.abierto) {
                this.cerrar();
                return;
            }

            this.anio = Number((this.valor || this.maximo).slice(0, 4));
            this.abierto = true;
        },

        cerrar(devolverFoco = false) {
            this.abierto = false;
            if (devolverFoco) {
                this.$refs.activador.focus();
            }
        },

        seleccionar(valor) {
            if (valor > this.maximo) {
                return;
            }

            this.valor = valor;
            this.$refs.campo.value = valor;
            this.cerrar(true);
        },
    };
}
