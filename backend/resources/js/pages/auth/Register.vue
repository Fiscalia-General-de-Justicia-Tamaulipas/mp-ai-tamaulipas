<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Alta institucional',
        description: 'Registro de acceso a la plataforma MP-IA',
    },
});
</script>

<template>
    <Head title="Alta institucional" />

    <div class="ai-register">
        <div class="register-grid" aria-hidden="true"></div>
        <div class="register-glow register-glow--left" aria-hidden="true"></div>
        <div class="register-glow register-glow--right" aria-hidden="true"></div>

        <main class="register-screen">
            <section class="register-institution">
                <div class="register-institution__content">
                    <img src="/images/logo_fgjtam.png" alt="Fiscalía General de Justicia del Estado de Tamaulipas" class="register-logo" />
                    <div class="register-rule"></div>
                    <p class="register-overline">PLATAFORMA INSTITUCIONAL</p>
                    <h1>MP <span>-</span> AI</h1>
                    <p class="register-intro">Crea tu acceso para trabajar con análisis jurídico, trazabilidad y revisión ministerial.</p>
                    <div class="register-status"><span class="register-status__dot"></span><span>SISTEMA OPERATIVO</span><i></i><strong>IA ACTIVA</strong></div>
                </div>

                <div class="register-orbit" aria-hidden="true">
                    <span class="register-orbit__ring register-orbit__ring--one"></span>
                    <span class="register-orbit__ring register-orbit__ring--two"></span>
                    <span class="register-orbit__core">✦</span>
                    <span class="register-orbit__node register-orbit__node--one"></span>
                    <span class="register-orbit__node register-orbit__node--two"></span>
                    <span class="register-orbit__node register-orbit__node--three"></span>
                </div>

                <div class="register-footer"><span>PLATAFORMA IA</span><b>/</b><span>ALTA INSTITUCIONAL</span></div>
            </section>

            <section class="register-panel">
                <div class="register-panel__inner">
                    <div class="register-header">
                        <div class="register-label"><span></span> ALTA DE USUARIO</div>
                        <h2>Crear acceso</h2>
                        <p>Completa tus datos para solicitar tu cuenta institucional.</p>
                    </div>

                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password', 'password_confirmation']"
                        v-slot="{ errors, processing }"
                        class="register-form"
                    >
                        <div class="register-field">
                            <Label for="name">Nombre completo</Label>
                            <div class="register-input" :class="{ 'register-input--error': errors.name }">
                                <span class="register-input__icon" aria-hidden="true">♙</span>
                                <Input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name" placeholder="Nombre y apellidos" />
                            </div>
                            <InputError :message="errors.name" />
                        </div>

                        <div class="register-field">
                            <Label for="email">Correo institucional</Label>
                            <div class="register-input" :class="{ 'register-input--error': errors.email }">
                                <span class="register-input__icon" aria-hidden="true">@</span>
                                <Input id="email" type="email" required :tabindex="2" autocomplete="email" name="email" placeholder="correo@institucion.gob.mx" />
                            </div>
                            <InputError :message="errors.email" />
                        </div>

                        <div class="register-password-grid">
                            <div class="register-field">
                                <Label for="password">Contraseña</Label>
                                <div class="register-input" :class="{ 'register-input--error': errors.password }">
                                    <span class="register-input__icon" aria-hidden="true">◆</span>
                                    <PasswordInput id="password" required :tabindex="3" autocomplete="new-password" name="password" placeholder="Crea una contraseña" :passwordrules="passwordRules" />
                                </div>
                                <InputError :message="errors.password" />
                            </div>

                            <div class="register-field">
                                <Label for="password_confirmation">Confirmar contraseña</Label>
                                <div class="register-input" :class="{ 'register-input--error': errors.password_confirmation }">
                                    <span class="register-input__icon" aria-hidden="true">✓</span>
                                    <PasswordInput id="password_confirmation" required :tabindex="4" autocomplete="new-password" name="password_confirmation" placeholder="Repite la contraseña" :passwordrules="passwordRules" />
                                </div>
                                <InputError :message="errors.password_confirmation" />
                            </div>
                        </div>

                        <div class="register-note"><span>◈</span><p>Usa una contraseña institucional y no la compartas con otras personas.</p></div>

                        <Button type="submit" class="register-submit" :tabindex="5" :disabled="processing" data-test="register-user-button">
                            <Spinner v-if="processing" />
                            <span>{{ processing ? 'Creando acceso...' : 'Crear cuenta institucional' }}</span>
                            <span v-if="!processing" class="register-submit__arrow">→</span>
                        </Button>
                    </Form>

                    <div class="register-login">¿Ya tienes una cuenta? <TextLink :href="login()" :tabindex="6">Ingresar al sistema</TextLink></div>
                    <div class="register-security"><span class="register-security__icon">✓</span><div><strong>Registro protegido</strong><small>Tu información se procesa dentro del acceso institucional.</small></div></div>
                    <p class="register-panel__footer">Sistema institucional de inteligencia artificial</p>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.ai-register { --ink: #173b32; --green: #168965; --mint: #68dfb5; position: relative; min-height: 100svh; overflow: hidden; background: linear-gradient(105deg, #575756 0%, #575756 51.9%, #f6f6f5 52%, #f6f6f5 100%); color: #575756; isolation: isolate; }
.register-grid { position: absolute; inset: 0; opacity: .12; background-image: linear-gradient(rgba(255,255,255,.2) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.2) 1px, transparent 1px); background-size: 52px 52px; pointer-events: none; }
.register-glow { position: absolute; width: 360px; height: 360px; border: 1px solid rgba(117,232,190,.12); border-radius: 50%; pointer-events: none; }.register-glow--left { top: 12%; left: 22%; }.register-glow--right { right: -150px; bottom: -190px; border-color: rgba(22,137,101,.08); }
.register-screen { position: relative; display: grid; grid-template-columns: minmax(320px,46%) minmax(440px,54%); min-height: 100svh; z-index: 1; }
.register-institution { position: relative; display: flex; flex-direction: column; justify-content: space-between; min-width: 0; padding: clamp(42px,7vw,96px) clamp(34px,7vw,110px) 34px; color: #f6f6f5; }.register-institution__content { max-width: 430px; }.register-logo { width: min(260px,70%); height: auto; filter: grayscale(1) brightness(0) invert(1); opacity: .82; }.register-rule { width: 64px; height: 2px; margin: 34px 0 28px; background: var(--mint); }.register-overline, .register-label { margin: 0; color: var(--mint); font-size: 10px; font-weight: 900; letter-spacing: .2em; }.register-institution h1 { margin: 14px 0 15px; color: #fff; font-size: clamp(42px,5vw,68px); font-weight: 300; letter-spacing: .05em; }.register-institution h1 span { color: var(--mint); }.register-intro { max-width: 370px; margin: 0; color: rgba(244,250,247,.68); font-size: 14px; line-height: 1.75; }.register-status { display: flex; align-items: center; gap: 9px; margin-top: 28px; color: rgba(237,250,245,.64); font-size: 9px; font-weight: 800; letter-spacing: .13em; }.register-status i { width: 3px; height: 3px; border-radius: 50%; background: rgba(237,250,245,.34); }.register-status strong { color: var(--mint); }.register-status__dot { width: 7px; height: 7px; border-radius: 50%; background: var(--mint); box-shadow: 0 0 0 5px rgba(104,223,181,.12); }.register-footer { display: flex; gap: 13px; color: rgba(231,243,238,.42); font-size: 9px; font-weight: 800; letter-spacing: .16em; }.register-footer b { color: var(--mint); font-weight: 400; }
.register-orbit { position: absolute; right: 10%; bottom: 16%; width: 190px; height: 190px; opacity: .58; }.register-orbit__ring { position: absolute; inset: 12px; border: 1px solid rgba(104,223,181,.3); border-radius: 50%; transform: rotate(26deg) skewX(-12deg); }.register-orbit__ring--two { inset: 30px -5px; transform: rotate(-34deg) skewX(14deg); }.register-orbit__core { position: absolute; inset: 74px; display: grid; place-items: center; border: 1px solid rgba(104,223,181,.55); border-radius: 50%; color: var(--mint); font-size: 20px; box-shadow: 0 0 30px rgba(104,223,181,.12); }.register-orbit__node { position: absolute; width: 7px; height: 7px; border-radius: 50%; background: var(--mint); box-shadow: 0 0 0 5px rgba(104,223,181,.1); }.register-orbit__node--one { top: 16px; right: 29px; }.register-orbit__node--two { bottom: 25px; left: 20px; }.register-orbit__node--three { top: 86px; right: -1px; }
.register-panel { display: flex; align-items: center; justify-content: center; min-width: 0; padding: 42px clamp(28px,7vw,115px); }.register-panel__inner { width: min(100%,570px); }.register-header { margin-bottom: 29px; }.register-label { display: flex; align-items: center; gap: 9px; color: var(--green); }.register-label span { width: 7px; height: 7px; border: 1px solid var(--green); border-radius: 50%; }.register-header h2 { margin: 14px 0 8px; color: #414141; font-size: clamp(30px,4vw,42px); font-weight: 400; letter-spacing: -.035em; }.register-header p { max-width: 420px; margin: 0; color: #858585; font-size: 13px; line-height: 1.6; }.register-form { display: grid; gap: 18px; }.register-field { display: grid; gap: 7px; min-width: 0; }.register-field :deep(label) { color: #656565; font-size: 10px; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }.register-input { display: flex; align-items: center; min-width: 0; height: 48px; border: 1px solid #dcdedc; border-radius: 7px; background: #fff; transition: border-color .2s, box-shadow .2s; }.register-input:focus-within { border-color: var(--green); box-shadow: 0 0 0 3px rgba(22,137,101,.1); }.register-input--error { border-color: #c4665b; }.register-input__icon { display: grid; width: 45px; flex: 0 0 45px; place-items: center; color: #9da5a1; font-size: 16px; }.register-input :deep(input) { min-width: 0; height: 100%; border: 0; outline: 0; box-shadow: none; color: #3f4a46; font-size: 13px; }.register-input :deep(input::placeholder) { color: #b3bab6; }.register-input :deep(button) { margin-right: 10px; }.register-field :deep(.text-destructive) { color: #b34e43; font-size: 11px; }.register-password-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px; }.register-note { display: flex; align-items: flex-start; gap: 9px; padding: 11px 13px; border: 1px solid #dfece5; border-radius: 7px; background: #f2faf6; color: #5e796d; }.register-note span { color: var(--green); font-size: 13px; }.register-note p { margin: 0; font-size: 11px; line-height: 1.5; }.register-submit { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; min-height: 49px; margin-top: 3px; border: 0; border-radius: 7px; background: var(--ink); color: #fff; font-size: 12px; font-weight: 800; letter-spacing: .03em; box-shadow: 0 9px 20px rgba(23,59,50,.14); }.register-submit:hover { background: #0e6d4e; }.register-submit:disabled { cursor: wait; opacity: .65; }.register-submit__arrow { color: var(--mint); font-size: 20px; line-height: 0; }.register-login { margin-top: 22px; color: #898989; font-size: 12px; text-align: center; }.register-login :deep(a) { margin-left: 5px; color: var(--green); font-weight: 800; text-decoration: underline; text-underline-offset: 3px; }.register-security { display: flex; align-items: center; gap: 11px; margin-top: 30px; padding-top: 17px; border-top: 1px solid #e4e5e3; }.register-security__icon { display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center; border: 1px solid #b9e4d1; border-radius: 50%; color: var(--green); font-size: 13px; }.register-security strong, .register-security small { display: block; }.register-security strong { color: #5c6863; font-size: 11px; }.register-security small { margin-top: 3px; color: #9aa19e; font-size: 10px; }.register-panel__footer { margin: 27px 0 0; color: #b3b5b3; font-size: 10px; text-align: center; }
@media (max-width:900px) { .register-screen { grid-template-columns: minmax(280px,39%) minmax(420px,61%); }.register-institution { padding-inline: 42px; }.register-orbit { right: 4%; transform: scale(.78); transform-origin: right bottom; }.register-panel { padding-inline: 38px; } }
@media (max-width:700px) { .ai-register { background: #f6f6f5; }.register-screen { display: block; }.register-institution { min-height: 285px; padding: 30px 24px 25px; background: #575756; }.register-logo { width: 190px; }.register-rule { margin: 20px 0 16px; }.register-institution h1 { margin: 8px 0; font-size: 42px; }.register-intro { display: none; }.register-status { margin-top: 18px; }.register-orbit { right: 10px; bottom: 3px; transform: scale(.54); }.register-footer { margin-top: 26px; }.register-panel { align-items: flex-start; min-height: calc(100svh - 285px); padding: 34px 22px 42px; }.register-panel__inner { max-width: 540px; }.register-header { margin-bottom: 24px; }.register-password-grid { grid-template-columns: 1fr; gap: 18px; } }
@media (prefers-reduced-motion:reduce) { .register-input, .register-submit { transition: none; } }
</style>
