# -*- coding: utf-8 -*-
"""
Gera o video de demonstracao do FarmaGest (MP4, 1920x1080, 30 fps).

    python tools\\gerar-video.py

O video e montado a partir das capturas de ecra reais da aplicacao
(docs\\imagens\\ecras) e dos diagramas UML (docs\\imagens). Cada cena e
composta em memoria com a biblioteca Pillow e enviada directamente para o
ffmpeg, sem escrever ficheiros intermedios no disco.

Requer: Pillow e ffmpeg acessivel na linha de comandos.
Antes de correr, actualize as capturas com:
    powershell -File tools\\capturar-ecras.ps1
"""

import math
import os
import shutil
import subprocess
import sys

from PIL import Image, ImageDraw, ImageFilter, ImageFont

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
IMAGENS = os.path.join(RAIZ, "docs", "imagens")
SAIDA = os.path.join(RAIZ, "docs", "FarmaGest-demonstracao.mp4")

LARGURA, ALTURA = 1920, 1080
FPS = 30
TRANSICAO = 0.5  # segundos de sobreposicao entre cenas

# ------------------------------------------------------------------ paleta ---

FUNDO = (10, 30, 24)
FUNDO_BAIXO = (5, 16, 13)
VERDE = (26, 138, 94)
VERDE_CLARO = (109, 209, 163)
BRANCO = (245, 250, 247)
CINZA = (150, 172, 163)
BARRA = (28, 46, 39)

FONTES = "C:\\Windows\\Fonts"


def fonte(ficheiro, tamanho):
    return ImageFont.truetype(os.path.join(FONTES, ficheiro), tamanho)


F_TITULO = fonte("seguisb.ttf", 46)
F_TITULO_G = fonte("seguisb.ttf", 92)
F_SUB = fonte("segoeui.ttf", 26)
F_SUB_G = fonte("segoeui.ttf", 34)
F_PEQ = fonte("segoeui.ttf", 22)
F_NUM = fonte("seguisb.ttf", 120)
F_COD = fonte("consola.ttf", 26)
F_URL = fonte("segoeui.ttf", 20)

# Area onde a captura e apresentada.
CAIXA = (80, 176, 1840, 1012)   # esquerda, topo, direita, fundo
BARRA_ALTURA = 46


# ------------------------------------------------------------- utilitarios ---


def gradiente(cima, baixo):
    """Fundo vertical, desenhado uma unica vez e reutilizado por todas as cenas."""
    faixa = Image.new("RGB", (1, ALTURA))
    pixeis = faixa.load()
    for y in range(ALTURA):
        k = y / (ALTURA - 1)
        pixeis[0, y] = tuple(round(cima[i] + (baixo[i] - cima[i]) * k) for i in range(3))
    return faixa.resize((LARGURA, ALTURA))


FUNDO_BASE = gradiente(FUNDO, FUNDO_BAIXO)


def suavizar(k):
    """Aceleracao e travagem suaves (smoothstep), para o movimento nao ser seco."""
    k = max(0.0, min(1.0, k))
    return k * k * (3 - 2 * k)


def texto(desenho, xy, conteudo, tipo, cor, espacamento=None):
    desenho.text(xy, conteudo, font=tipo, fill=cor,
                 spacing=espacamento if espacamento is not None else 4)


def cartao(base, caixa, raio=14, sombra=True, barra=True):
    """Desenha a moldura da janela onde a captura vai ser colada."""
    x0, y0, x1, y1 = caixa
    if sombra:
        camada = Image.new("RGB", (LARGURA, ALTURA), (0, 0, 0))
        mascara = Image.new("L", (LARGURA, ALTURA), 0)
        ImageDraw.Draw(mascara).rounded_rectangle(
            (x0 + 6, y0 + 14, x1 + 6, y1 + 18), raio + 6, fill=110
        )
        base.paste(camada, (0, 0), mascara.filter(ImageFilter.GaussianBlur(18)))

    d = ImageDraw.Draw(base)
    d.rounded_rectangle((x0, y0, x1, y1), raio, fill=(255, 255, 255))
    if not barra:
        return

    # Barra de titulo da "janela".
    d.rounded_rectangle((x0, y0, x1, y0 + BARRA_ALTURA + raio), raio, fill=(232, 238, 235))
    d.rectangle((x0, y0 + BARRA_ALTURA, x1, y0 + BARRA_ALTURA + 2), fill=(214, 224, 219))
    for i, cor in enumerate(((236, 106, 95), (245, 191, 79), (98, 197, 84))):
        cx = x0 + 26 + i * 22
        cy = y0 + BARRA_ALTURA // 2
        d.ellipse((cx - 6, cy - 6, cx + 6, cy + 6), fill=cor)


def barra_endereco(base, caixa, endereco):
    x0, y0, x1, _ = caixa
    d = ImageDraw.Draw(base)
    largura = 520
    cx = (x0 + x1) // 2 - largura // 2
    cy = y0 + BARRA_ALTURA // 2
    d.rounded_rectangle((cx, cy - 15, cx + largura, cy + 15), 15, fill=(248, 250, 249))
    d.text((cx + 18, cy - 11), endereco, font=F_URL, fill=(110, 128, 120))


# ------------------------------------------------------------------- cenas ---


class Cena:
    def __init__(self, duracao):
        self.duracao = duracao

    def total(self):
        return int(round(self.duracao * FPS))

    def frame(self, i):
        raise NotImplementedError


class CenaCaptura(Cena):
    """Captura de ecra apresentada numa janela, com deslocamento vertical lento."""

    def __init__(self, ficheiro, numero, titulo, subtitulo, duracao,
                 endereco="localhost/farmagest", modo="auto", cromo=True):
        super().__init__(duracao)
        self.base = FUNDO_BASE.copy()

        d = ImageDraw.Draw(self.base)
        if numero:
            d.rounded_rectangle((80, 52, 80 + 54, 52 + 54), 12, fill=VERDE)
            largura_n = d.textlength(numero, font=F_TITULO)
            d.text((80 + 27 - largura_n / 2, 55), numero, font=F_TITULO, fill=BRANCO)
            x = 156
        else:
            x = 80
        texto(d, (x, 46), titulo, F_TITULO, BRANCO)
        texto(d, (x, 104), subtitulo, F_SUB, CINZA)

        x0, y0, x1, y1 = CAIXA
        # Os diagramas nao sao paginas Web: sao apresentados numa folha simples,
        # sem a moldura de navegador usada para as capturas da aplicacao.
        cartao(self.base, CAIXA, barra=cromo)
        if cromo:
            barra_endereco(self.base, CAIXA, endereco)

        # Zona util para a imagem, ja abaixo da barra de titulo.
        topo_vista = y0 + BARRA_ALTURA + 2 if cromo else y0 + 12
        self.vista = (x0 + 1, topo_vista, x1 - 1, y1 - 1)
        vw = self.vista[2] - self.vista[0]
        vh = self.vista[3] - self.vista[1]

        imagem = Image.open(os.path.join(IMAGENS, ficheiro)).convert("RGB")
        altura_por_largura = round(imagem.height * vw / imagem.width)

        # Panoramica so quando o excesso e moderado; caso contrario mostra-se a
        # imagem inteira, que e preferivel a um deslocamento interminavel.
        if modo == "inteiro" or (modo == "auto" and altura_por_largura > vh * 1.6):
            nova_altura = vh
            nova_largura = round(imagem.width * vh / imagem.height)
            self.escalada = imagem.resize((nova_largura, nova_altura), Image.LANCZOS)
            self.desvio = 0
            self.margem = (vw - nova_largura) // 2
        else:
            self.escalada = imagem.resize((vw, altura_por_largura), Image.LANCZOS)
            self.desvio = max(0, altura_por_largura - vh)
            self.margem = 0

        self.vw, self.vh = vw, vh

    def frame(self, i):
        quadro = self.base.copy()
        k = i / max(1, self.total() - 1)
        topo = round(self.desvio * suavizar(k))
        recorte = self.escalada.crop((0, topo, self.escalada.width, topo + min(self.vh, self.escalada.height)))
        quadro.paste(recorte, (self.vista[0] + self.margem, self.vista[1]))
        return quadro


class CenaCartao(Cena):
    """Cena de texto: abertura, explicacoes e fecho."""

    def __init__(self, duracao, desenhar):
        super().__init__(duracao)
        self.base = FUNDO_BASE.copy()
        desenhar(self.base, ImageDraw.Draw(self.base))

    def frame(self, i):
        return self.base


def capa(base, d):
    d.rounded_rectangle((0, 0, LARGURA, 8), 0, fill=VERDE)
    largura = d.textlength("FarmaGest", font=F_TITULO_G)
    x = (LARGURA - largura) / 2
    d.text((x, 322), "FarmaGest", font=F_TITULO_G, fill=BRANCO)

    for linha, tipo, cor, y in (
        ("Sistema de Gestão de Farmácia", F_SUB_G, VERDE_CLARO, 440),
        ("Aplicação Web em PHP 8 sobre arquitectura MVC,", F_SUB, CINZA, 512),
        ("com abate de stock por lote segundo o critério FEFO", F_SUB, CINZA, 550),
        ("Nordino Elias Jossias Uamusse  ·  31240558", F_SUB, BRANCO, 660),
        ("UniSCED  ·  Desenvolvimento de Aplicativos Web Empresariais", F_PEQ, CINZA, 706),
    ):
        largura = d.textlength(linha, font=tipo)
        d.text(((LARGURA - largura) / 2, y), linha, font=tipo, fill=cor)

    d.rectangle((LARGURA / 2 - 60, 616, LARGURA / 2 + 60, 618), fill=VERDE)


def fefo(base, d):
    texto(d, (80, 46), "A regra central: FEFO", F_TITULO, BRANCO)
    texto(d, (80, 104), "First Expired, First Out — sai sempre o lote que expira primeiro",
          F_SUB, CINZA)

    d.rounded_rectangle((80, 200, 940, 796), 14, fill=BARRA)
    linhas = [
        ("O medicamento não segue FIFO.", BRANCO),
        ("", CINZA),
        ("Dois lotes recebidos com uma semana de", CINZA),
        ("intervalo podem ter validades separadas", CINZA),
        ("por anos. Consumir por ordem de entrada", CINZA),
        ("deixaria expirar em prateleira o lote de", CINZA),
        ("validade mais curta.", CINZA),
        ("", CINZA),
        ("Por isso o stock vive nos lotes e nunca", BRANCO),
        ("no medicamento: só o lote tem validade.", BRANCO),
    ]
    y = 250
    for linha, cor in linhas:
        texto(d, (120, y), linha, F_SUB, cor)
        y += 50

    d.rounded_rectangle((980, 200, 1840, 796), 14, fill=(14, 24, 20))
    d.rounded_rectangle((980, 200, 1840, 246), 14, fill=VERDE)
    d.rectangle((980, 230, 1840, 246), fill=VERDE)
    texto(d, (1004, 210), "app\\Models\\Venda.php  ·  abaterStock()", F_PEQ, BRANCO)

    sql = [
        "SELECT id, numero_lote, quantidade_atual",
        "  FROM lotes",
        " WHERE medicamento_id = :id",
        "   AND quantidade_atual > 0",
        "   AND data_validade  >= CURDATE()",
        " ORDER BY data_validade ASC, id ASC",
        "   FOR UPDATE",
    ]
    y = 300
    for linha in sql:
        cor = VERDE_CLARO if ("ORDER BY" in linha or "FOR UPDATE" in linha) else (206, 224, 214)
        texto(d, (1010, y), linha, F_COD, cor)
        y += 44

    texto(d, (1010, 638),
          "Transacção atómica com bloqueio de linhas:\nduas vendas simultâneas nunca vendem o mesmo stock.\n"
          "Qualquer excepção reverte tudo o que já tinha sido gravado.",
          F_PEQ, CINZA, espacamento=12)

    texto(d, (80, 860),
          "A venda de uma única quantidade pode originar várias linhas em itens_venda, uma por cada lote consumido.",
          F_SUB, CINZA)
    texto(d, (80, 908),
          "É o que garante a rastreabilidade: para qualquer venda sabe-se de que lote saiu cada unidade.",
          F_SUB, CINZA)


def testes(base, d):
    texto(d, (80, 46), "Verificação", F_TITULO, BRANCO)
    texto(d, (80, 104), "Todo o sistema é verificado automaticamente", F_SUB, CINZA)

    blocos = [
        ("48/48", "asserções sobre a lógica de negócio", "php tests\\executar.php"),
        ("50/50", "rotas verificadas por HTTP", "powershell -File tests\\crawl.ps1"),
        ("3", "formas de instalação suportadas", "PHP embutido  ·  XAMPP  ·  /public"),
    ]
    x = 80
    for numero, descricao, comando in blocos:
        d.rounded_rectangle((x, 240, x + 546, 720), 14, fill=BARRA)
        d.rectangle((x, 240, x + 6, 720), fill=VERDE)
        largura = d.textlength(numero, font=F_NUM)
        d.text((x + 273 - largura / 2, 330), numero, font=F_NUM, fill=VERDE_CLARO)
        largura = d.textlength(descricao, font=F_SUB)
        d.text((x + 273 - largura / 2, 520), descricao, font=F_SUB, fill=BRANCO)
        largura = d.textlength(comando, font=F_PEQ)
        d.text((x + 273 - largura / 2, 582), comando, font=F_PEQ, fill=CINZA)
        x += 587

    texto(d, (80, 800),
          "Os testes de negócio repõem o estado da base de dados no fim, pelo que podem correr repetidamente.",
          F_SUB, CINZA)
    texto(d, (80, 848),
          "O teste de rotas aceita o endereço de base, permitindo verificar qualquer uma das instalações.",
          F_SUB, CINZA)


def fecho(base, d):
    largura = d.textlength("FarmaGest", font=F_TITULO_G)
    d.text(((LARGURA - largura) / 2, 340), "FarmaGest", font=F_TITULO_G, fill=BRANCO)
    d.rectangle((LARGURA / 2 - 60, 470, LARGURA / 2 + 60, 472), fill=VERDE)
    for linha, tipo, cor, y in (
        ("Obrigado pela atenção", F_SUB_G, VERDE_CLARO, 510),
        ("Nordino Elias Jossias Uamusse  ·  31240558", F_SUB, BRANCO, 606),
        ("UniSCED  ·  Moçambique, 2026", F_PEQ, CINZA, 650),
    ):
        largura = d.textlength(linha, font=tipo)
        d.text(((LARGURA - largura) / 2, y), linha, font=tipo, fill=cor)


# ---------------------------------------------------------------- montagem ---

def construir():
    e = os.path.join("ecras", "")
    return [
        CenaCartao(5.0, capa),
        CenaCaptura(e + "01-login.png", "1", "Autenticação",
                    "Três perfis: administrador, farmacêutico e operador de caixa", 5.0,
                    "localhost/farmagest/login"),
        CenaCaptura(e + "02-painel.png", "2", "Painel de controlo",
                    "Indicadores do dia, evolução das vendas e alertas de stock", 8.0,
                    "localhost/farmagest/painel"),
        CenaCaptura(e + "03-medicamentos.png", "3", "Catálogo de medicamentos",
                    "Pesquisa, filtro por categoria e stock calculado a partir dos lotes", 6.0,
                    "localhost/farmagest/medicamentos"),
        CenaCaptura(e + "04-lotes-validade.png", "4", "Controlo de validades",
                    "Lotes a expirar nos próximos noventa dias, por ordem de urgência", 6.0,
                    "localhost/farmagest/lotes?filtro=a_expirar"),
        CenaCaptura(e + "05-registar-venda.png", "5", "Registo de venda",
                    "Catálogo à esquerda, carrinho à direita", 5.0,
                    "localhost/farmagest/vendas/criar"),
        CenaCaptura(e + "09-carrinho.png", "6", "Carrinho e totais",
                    "Três medicamentos, desconto aplicado, total calculado em tempo real", 7.0,
                    "localhost/farmagest/vendas/criar"),
        CenaCartao(8.0, fefo),
        CenaCaptura(e + "06-recibo.png", "7", "Recibo da venda",
                    "Documento imprimível, com o lote de origem de cada item", 5.0,
                    "localhost/farmagest/vendas/95/recibo"),
        CenaCaptura(e + "07-relatorio-vendas.png", "8", "Relatórios",
                    "Vendas por período, por forma de pagamento e por operador", 7.0,
                    "localhost/farmagest/relatorios/vendas"),
        CenaCaptura(e + "08-utilizadores.png", "9", "Utilizadores e perfis",
                    "Área reservada ao administrador", 5.0,
                    "localhost/farmagest/utilizadores"),
        CenaCaptura("02-diagrama-de-classes.png", "10", "Arquitectura",
                    "Núcleo reutilizável e modelos de domínio", 5.0,
                    modo="inteiro", cromo=False),
        CenaCaptura("03-sequencia-registar-venda.png", "11", "Fluxo do abate FEFO",
                    "Transacção, ciclo pelos itens e ciclo pelos lotes por validade", 6.0,
                    modo="inteiro", cromo=False),
        CenaCartao(5.0, testes),
        CenaCartao(5.0, fecho),
    ]


def escrever(cenas, destino):
    ffmpeg = shutil.which("ffmpeg")
    if not ffmpeg:
        sys.exit("ffmpeg nao foi encontrado na linha de comandos.")

    comando = [
        ffmpeg, "-y", "-loglevel", "error",
        "-f", "rawvideo", "-pix_fmt", "rgb24",
        "-s", "%dx%d" % (LARGURA, ALTURA), "-r", str(FPS),
        "-i", "-",
        "-c:v", "libx264", "-preset", "medium", "-crf", "20",
        "-pix_fmt", "yuv420p", "-movflags", "+faststart",
        destino,
    ]

    sobreposicao = int(round(TRANSICAO * FPS))
    total = sum(c.total() for c in cenas) - sobreposicao * (len(cenas) - 1)
    escritos = 0

    processo = subprocess.Popen(comando, stdin=subprocess.PIPE)
    try:
        for indice, cena in enumerate(cenas):
            n = cena.total()
            # Os ultimos quadros de cada cena sao fundidos com os primeiros da
            # seguinte, por isso nao sao escritos aqui.
            fim = n if indice == len(cenas) - 1 else n - sobreposicao

            for i in range(fim):
                quadro = cena.frame(i)
                if indice == 0 and i < FPS:                     # abertura a negro
                    quadro = Image.blend(Image.new("RGB", quadro.size), quadro, i / FPS)
                if indice == len(cenas) - 1 and i >= n - FPS:   # fecho a negro
                    k = (n - i) / FPS
                    quadro = Image.blend(Image.new("RGB", quadro.size), quadro, k)
                processo.stdin.write(quadro.tobytes())
                escritos += 1

            if indice < len(cenas) - 1:
                seguinte = cenas[indice + 1]
                for j in range(sobreposicao):
                    k = (j + 1) / (sobreposicao + 1)
                    quadro = Image.blend(cena.frame(fim + j), seguinte.frame(j), k)
                    processo.stdin.write(quadro.tobytes())
                    escritos += 1

            print("  cena %2d/%d  %5.1f s  (%d quadros)" % (
                indice + 1, len(cenas), cena.duracao, n))

        processo.stdin.close()
    finally:
        codigo = processo.wait()

    if codigo != 0:
        sys.exit("O ffmpeg terminou com o codigo %d." % codigo)

    return escritos, total


if __name__ == "__main__":
    cenas = construir()
    print("A montar %d cenas..." % len(cenas))
    escritos, previstos = escrever(cenas, SAIDA)
    tamanho = os.path.getsize(SAIDA) / (1024 * 1024)
    print("")
    print("Video: %s" % SAIDA)
    print("Duracao: %.1f s | %d quadros | %.1f MB" % (escritos / FPS, escritos, tamanho))
