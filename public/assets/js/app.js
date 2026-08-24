/**
 * FarmaGest - comportamento do lado do cliente.
 * A validacao aqui e apenas uma conveniencia: o servidor revalida sempre tudo.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        activarValidacaoBootstrap();
        activarConfirmacoes();
        activarPesquisaTabela();
        activarFecharAlertas();
        Carrinho.iniciar();
    });

    /** Aplica o estilo de validacao do Bootstrap 5 aos formularios marcados. */
    function activarValidacaoBootstrap() {
        document.querySelectorAll('form.needs-validation').forEach(function (formulario) {
            formulario.addEventListener('submit', function (evento) {
                if (!formulario.checkValidity()) {
                    evento.preventDefault();
                    evento.stopPropagation();
                    const primeiro = formulario.querySelector(':invalid');
                    if (primeiro) {
                        primeiro.focus();
                    }
                }
                formulario.classList.add('was-validated');
            }, false);
        });
    }

    /** Pede confirmacao antes de accoes destrutivas. */
    function activarConfirmacoes() {
        document.querySelectorAll('[data-confirmar]').forEach(function (elemento) {
            elemento.addEventListener('submit', function (evento) {
                if (!window.confirm(elemento.dataset.confirmar)) {
                    evento.preventDefault();
                }
            });
        });
    }

    /** Filtro instantaneo de linhas numa tabela (complementa a pesquisa no servidor). */
    function activarPesquisaTabela() {
        document.querySelectorAll('[data-filtra-tabela]').forEach(function (campo) {
            const tabela = document.querySelector(campo.dataset.filtraTabela);
            if (!tabela) {
                return;
            }
            campo.addEventListener('input', function () {
                const termo = campo.value.trim().toLowerCase();
                tabela.querySelectorAll('tbody tr').forEach(function (linha) {
                    linha.hidden = termo !== '' && !linha.textContent.toLowerCase().includes(termo);
                });
            });
        });
    }

    function activarFecharAlertas() {
        document.querySelectorAll('.alert-dismissible').forEach(function (alerta) {
            window.setTimeout(function () {
                if (window.bootstrap && bootstrap.Alert.getOrCreateInstance) {
                    bootstrap.Alert.getOrCreateInstance(alerta).close();
                }
            }, 8000);
        });
    }

    /** ------------------------------------------------------------------
     * Carrinho do ecra de venda.
     * Mantem um mapa medicamentoId -> linha e recalcula os totais.
     * ------------------------------------------------------------------ */
    const Carrinho = {
        itens: new Map(),
        catalogo: [],

        iniciar: function () {
            this.ecra = document.getElementById('ecra-venda');
            if (!this.ecra) {
                return;
            }

            this.catalogo = JSON.parse(document.getElementById('catalogo-medicamentos').textContent);
            this.corpo = document.getElementById('carrinho-corpo');
            this.vazio = document.getElementById('carrinho-vazio');
            this.campoDesconto = document.getElementById('desconto');
            this.formulario = document.getElementById('formulario-venda');

            const self = this;

            document.querySelectorAll('[data-adicionar]').forEach(function (botao) {
                botao.addEventListener('click', function () {
                    self.adicionar(parseInt(botao.dataset.adicionar, 10));
                });
            });

            this.campoDesconto.addEventListener('input', function () {
                self.recalcular();
            });

            this.formulario.addEventListener('submit', function (evento) {
                if (self.itens.size === 0) {
                    evento.preventDefault();
                    window.alert('Adicione pelo menos um medicamento ao carrinho.');
                }
            });

            this.recalcular();
        },

        produto: function (id) {
            return this.catalogo.find(function (p) {
                return p.id === id;
            });
        },

        adicionar: function (id) {
            const produto = this.produto(id);
            if (!produto) {
                return;
            }

            if (this.itens.has(id)) {
                const linha = this.itens.get(id);
                const campo = linha.querySelector('.campo-quantidade');
                const nova = parseInt(campo.value, 10) + 1;
                if (nova > produto.stock) {
                    window.alert('Stock insuficiente. Disponivel: ' + produto.stock + ' unidades.');
                    return;
                }
                campo.value = nova;
                this.recalcular();
                return;
            }

            this.criarLinha(produto);
            this.recalcular();
        },

        criarLinha: function (produto) {
            const self = this;
            const linha = document.createElement('tr');

            linha.innerHTML =
                '<td>' +
                '  <div class="fw-semibold">' + escaparHtml(produto.nome) + '</div>' +
                '  <div class="small text-muted">' + escaparHtml(produto.codigo) +
                (produto.receita ? ' <span class="badge text-bg-warning">Receita</span>' : '') +
                '  </div>' +
                '  <input type="hidden" name="item_medicamento[]" value="' + produto.id + '">' +
                '</td>' +
                '<td class="text-end">' + formatar(produto.preco) + '</td>' +
                '<td style="width: 120px;">' +
                '  <input type="number" class="form-control form-control-sm campo-quantidade"' +
                '         name="item_quantidade[]" value="1" min="1" max="' + produto.stock + '" required>' +
                '</td>' +
                '<td class="text-end fw-semibold coluna-subtotal">' + formatar(produto.preco) + '</td>' +
                '<td class="text-end">' +
                '  <button type="button" class="btn btn-sm btn-outline-danger" title="Remover">' +
                '    <i class="bi bi-trash"></i>' +
                '  </button>' +
                '</td>';

            linha.querySelector('.campo-quantidade').addEventListener('input', function () {
                const campo = this;
                if (parseInt(campo.value, 10) > produto.stock) {
                    campo.value = produto.stock;
                    window.alert('Stock insuficiente. Disponivel: ' + produto.stock + ' unidades.');
                }
                if (campo.value === '' || parseInt(campo.value, 10) < 1) {
                    campo.value = 1;
                }
                self.recalcular();
            });

            linha.querySelector('button').addEventListener('click', function () {
                linha.remove();
                self.itens.delete(produto.id);
                self.recalcular();
            });

            this.corpo.appendChild(linha);
            this.itens.set(produto.id, linha);
        },

        recalcular: function () {
            const self = this;
            let subtotal = 0;

            this.itens.forEach(function (linha, id) {
                const produto = self.produto(id);
                const quantidade = parseInt(linha.querySelector('.campo-quantidade').value, 10) || 0;
                const valor = quantidade * produto.preco;
                linha.querySelector('.coluna-subtotal').textContent = formatar(valor);
                subtotal += valor;
            });

            let desconto = parseFloat(this.campoDesconto.value) || 0;
            if (desconto > subtotal) {
                desconto = subtotal;
                this.campoDesconto.value = subtotal.toFixed(2);
            }
            if (desconto < 0) {
                desconto = 0;
                this.campoDesconto.value = '0';
            }

            document.getElementById('resumo-subtotal').textContent = formatar(subtotal);
            document.getElementById('resumo-desconto').textContent = formatar(desconto);
            document.getElementById('resumo-total').textContent = formatar(subtotal - desconto);

            this.vazio.hidden = this.itens.size > 0;
            document.getElementById('botao-finalizar').disabled = this.itens.size === 0;
        }
    };

    function formatar(valor) {
        return valor.toFixed(2).replace('.', ',') + ' MZN';
    }

    function escaparHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
    }
})();
