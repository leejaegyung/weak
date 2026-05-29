<template>
  <div class="login-page">

    <!-- ============ LEFT: Illustration Panel ============ -->
    <div class="illus-panel">
      <!-- Brand -->
      <div class="brand-mark">
        <div class="brand-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>
          </svg>
        </div>
        <span>주간업무보고</span>
      </div>

      <!-- SVG Illustration -->
      <div class="illus-inner">
        <svg viewBox="0 0 500 370" xmlns="http://www.w3.org/2000/svg" class="illus-svg">
          <defs>
            <linearGradient id="bgGrad" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#0D1B35"/>
              <stop offset="100%" stop-color="#152A48"/>
            </linearGradient>
            <linearGradient id="screenGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#0F2040"/>
              <stop offset="100%" stop-color="#081525"/>
            </linearGradient>
            <radialGradient id="glowOrange" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#FD4401" stop-opacity="0.22"/>
              <stop offset="100%" stop-color="#FD4401" stop-opacity="0"/>
            </radialGradient>
            <radialGradient id="glowBlue" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#4A9EFF" stop-opacity="0.18"/>
              <stop offset="100%" stop-color="#4A9EFF" stop-opacity="0"/>
            </radialGradient>
          </defs>

          <!-- Background -->
          <rect width="500" height="370" fill="url(#bgGrad)" rx="0"/>

          <!-- Circuit board lines -->
          <line x1="0" y1="115" x2="95" y2="115" stroke="#1E3060" stroke-width="1" opacity="0.55"/>
          <line x1="95" y1="115" x2="95" y2="72" stroke="#1E3060" stroke-width="1" opacity="0.55"/>
          <line x1="95" y1="72" x2="138" y2="72" stroke="#1E3060" stroke-width="1" opacity="0.55"/>
          <circle cx="95" cy="115" r="3" fill="#1E3060" opacity="0.7"/>
          <circle cx="95" cy="72" r="3" fill="#1E3060" opacity="0.7"/>
          <line x1="500" y1="170" x2="405" y2="170" stroke="#1E3060" stroke-width="1" opacity="0.55"/>
          <line x1="405" y1="170" x2="405" y2="215" stroke="#1E3060" stroke-width="1" opacity="0.55"/>
          <line x1="405" y1="215" x2="368" y2="215" stroke="#1E3060" stroke-width="1" opacity="0.55"/>
          <circle cx="405" cy="170" r="3" fill="#1E3060" opacity="0.7"/>
          <circle cx="405" cy="215" r="3" fill="#1E3060" opacity="0.7"/>
          <line x1="45" y1="295" x2="45" y2="355" stroke="#1E3060" stroke-width="1" opacity="0.45"/>
          <line x1="45" y1="355" x2="120" y2="355" stroke="#1E3060" stroke-width="1" opacity="0.45"/>
          <circle cx="45" cy="355" r="2.5" fill="#1E3060" opacity="0.6"/>
          <line x1="458" y1="338" x2="500" y2="338" stroke="#1E3060" stroke-width="1" opacity="0.45"/>
          <line x1="458" y1="310" x2="458" y2="338" stroke="#1E3060" stroke-width="1" opacity="0.45"/>
          <circle cx="458" cy="338" r="2.5" fill="#1E3060" opacity="0.6"/>

          <!-- Glow halos -->
          <ellipse cx="230" cy="195" rx="165" ry="135" fill="url(#glowOrange)"/>
          <ellipse cx="405" cy="128" rx="105" ry="82" fill="url(#glowBlue)"/>

          <!-- ===== FLOATING CARD 1: Weekly Stats (top-left) ===== -->
          <g transform="translate(10,32) rotate(-6,54,38)">
            <rect width="110" height="74" rx="12" fill="#162850" stroke="#2A4A80" stroke-width="1.5"/>
            <rect x="10" y="13" width="54" height="7" rx="3.5" fill="#FD4401" opacity="0.88"/>
            <rect x="10" y="26" width="90" height="4" rx="2" fill="white" opacity="0.18"/>
            <rect x="10" y="34" width="72" height="4" rx="2" fill="white" opacity="0.18"/>
            <!-- Mini bars -->
            <rect x="10" y="53" width="11" height="13" rx="2.5" fill="#FD4401" opacity="0.65"/>
            <rect x="25" y="46" width="11" height="20" rx="2.5" fill="#FD4401" opacity="0.85"/>
            <rect x="40" y="50" width="11" height="16" rx="2.5" fill="#FD4401" opacity="0.65"/>
            <rect x="55" y="42" width="11" height="24" rx="2.5" fill="#FEE500" opacity="0.9"/>
            <rect x="70" y="48" width="11" height="18" rx="2.5" fill="#FD4401" opacity="0.72"/>
            <line x1="8" y1="66" x2="102" y2="66" stroke="white" stroke-width="0.8" opacity="0.12"/>
          </g>

          <!-- ===== FLOATING CARD 2: Team Status (top-right) ===== -->
          <g transform="translate(378,26) rotate(6,50,34)">
            <rect width="102" height="68" rx="12" fill="#162850" stroke="#FEE500" stroke-width="1.5" opacity="0.92"/>
            <rect x="10" y="13" width="50" height="7" rx="3.5" fill="#FEE500" opacity="0.88"/>
            <!-- Avatar circles -->
            <circle cx="21" cy="42" r="12" fill="#FF8C42"/>
            <text x="21" y="46" font-size="10" fill="white" text-anchor="middle" font-weight="700">이</text>
            <circle cx="43" cy="42" r="12" fill="#4A9EFF"/>
            <text x="43" y="46" font-size="10" fill="white" text-anchor="middle" font-weight="700">김</text>
            <circle cx="65" cy="42" r="12" fill="#4AFF91"/>
            <text x="65" y="46" font-size="10" fill="#0D1B35" text-anchor="middle" font-weight="700">박</text>
            <circle cx="87" cy="42" r="12" fill="#FF6B9D"/>
            <text x="87" y="46" font-size="10" fill="white" text-anchor="middle" font-weight="700">최</text>
            <!-- Progress bar -->
            <rect x="10" y="58" width="82" height="5" rx="2.5" fill="#0D1B35" opacity="0.55"/>
            <rect x="10" y="58" width="58" height="5" rx="2.5" fill="#FD4401" opacity="0.82"/>
          </g>

          <!-- ===== FLOATING CARD 3: Report Document (bottom-right) ===== -->
          <g transform="translate(402,256) rotate(5,41,50)">
            <rect width="84" height="100" rx="10" fill="#162850" stroke="#4A9EFF" stroke-width="1.2" opacity="0.93"/>
            <!-- Corner fold -->
            <path d="M60 0 L84 26 L60 26 Z" fill="#0D2040"/>
            <line x1="60" y1="0" x2="84" y2="26" stroke="#4A9EFF" stroke-width="1" opacity="0.45"/>
            <!-- Lines -->
            <rect x="10" y="35" width="63" height="4" rx="2" fill="white" opacity="0.2"/>
            <rect x="10" y="45" width="53" height="4" rx="2" fill="white" opacity="0.2"/>
            <rect x="10" y="55" width="58" height="4" rx="2" fill="white" opacity="0.2"/>
            <rect x="10" y="65" width="46" height="4" rx="2" fill="white" opacity="0.2"/>
            <rect x="10" y="75" width="56" height="4" rx="2" fill="white" opacity="0.2"/>
            <!-- Stamp -->
            <g transform="rotate(-12,50,76)">
              <rect x="28" y="66" width="36" height="20" rx="5" fill="#FD4401" opacity="0.9"/>
              <text x="46" y="80" font-family="'Space Grotesk',sans-serif" font-size="8.5" font-weight="800" fill="white" text-anchor="middle">완료 ✓</text>
            </g>
          </g>

          <!-- ===== MAIN MONITOR ===== -->
          <!-- Shadow -->
          <rect x="142" y="89" width="216" height="157" rx="14" fill="#000" opacity="0.3" transform="translate(6,6)"/>
          <!-- Frame -->
          <rect x="142" y="89" width="216" height="157" rx="14" fill="#1A3260" stroke="#3A5A90" stroke-width="2"/>
          <!-- Bezel -->
          <rect x="150" y="97" width="200" height="141" rx="9" fill="url(#screenGrad)"/>
          <!-- Title bar -->
          <rect x="150" y="97" width="200" height="24" rx="9" fill="#FD4401"/>
          <rect x="150" y="109" width="200" height="12" fill="#FD4401"/>
          <!-- Title text placeholder -->
          <rect x="160" y="103" width="88" height="8" rx="4" fill="white" opacity="0.88"/>
          <!-- Window buttons -->
          <circle cx="308" cy="107" r="4" fill="white" opacity="0.22"/>
          <circle cx="322" cy="107" r="4" fill="white" opacity="0.22"/>
          <circle cx="336" cy="107" r="4" fill="white" opacity="0.22"/>

          <!-- Screen: left column (text rows) -->
          <rect x="158" y="130" width="78" height="7" rx="3.5" fill="#4A9EFF" opacity="0.7"/>
          <rect x="158" y="143" width="60" height="5" rx="2.5" fill="white" opacity="0.22"/>
          <rect x="158" y="154" width="68" height="5" rx="2.5" fill="white" opacity="0.22"/>
          <rect x="158" y="165" width="56" height="5" rx="2.5" fill="white" opacity="0.22"/>
          <rect x="158" y="176" width="72" height="5" rx="2.5" fill="white" opacity="0.22"/>
          <rect x="158" y="187" width="50" height="5" rx="2.5" fill="white" opacity="0.22"/>
          <rect x="158" y="198" width="64" height="5" rx="2.5" fill="white" opacity="0.22"/>
          <rect x="158" y="209" width="55" height="5" rx="2.5" fill="white" opacity="0.22"/>

          <!-- Screen: bar chart -->
          <rect x="252" y="202" width="13" height="30" rx="3" fill="#FD4401" opacity="0.72"/>
          <rect x="269" y="184" width="13" height="48" rx="3" fill="#FD4401" opacity="0.88"/>
          <rect x="286" y="192" width="13" height="40" rx="3" fill="#FD4401" opacity="0.72"/>
          <rect x="303" y="170" width="13" height="62" rx="3" fill="#FEE500" opacity="0.9"/>
          <rect x="320" y="180" width="13" height="52" rx="3" fill="#FD4401" opacity="0.68"/>
          <line x1="248" y1="232" x2="340" y2="232" stroke="white" stroke-width="0.8" opacity="0.18"/>

          <!-- Screen: line chart -->
          <polyline points="252,168 269,154 286,160 303,143 320,148 337,135" fill="none" stroke="#4AFF91" stroke-width="2.2" opacity="0.75"/>
          <circle cx="252" cy="168" r="3" fill="#4AFF91" opacity="0.75"/>
          <circle cx="303" cy="143" r="3" fill="#4AFF91" opacity="0.75"/>
          <circle cx="337" cy="135" r="4" fill="#4AFF91"/>

          <!-- Monitor stand -->
          <rect x="232" y="246" width="36" height="10" rx="3" fill="#1A3260" stroke="#2A4070" stroke-width="1.5"/>
          <rect x="207" y="256" width="86" height="8" rx="4" fill="#1A3260" stroke="#2A4070" stroke-width="1.5"/>

          <!-- ===== PERSON 1: Sitting at desk (left) ===== -->
          <!-- Desk surface -->
          <rect x="8" y="272" width="138" height="11" rx="5.5" fill="#1A3260" stroke="#2A4070" stroke-width="1.5"/>
          <rect x="20" y="283" width="6" height="26" rx="3" fill="#152550"/>
          <rect x="132" y="283" width="6" height="26" rx="3" fill="#152550"/>
          <!-- Laptop on desk -->
          <rect x="52" y="249" width="72" height="24" rx="5" fill="#1E3870" stroke="#4A9EFF" stroke-width="1.2"/>
          <rect x="59" y="255" width="58" height="15" rx="3" fill="#0D1B35"/>
          <rect x="62" y="259" width="36" height="5" rx="2.5" fill="#FD4401" opacity="0.65"/>
          <rect x="62" y="266" width="26" height="3.5" rx="1.5" fill="white" opacity="0.22"/>
          <rect x="62" y="271" width="32" height="3.5" rx="1.5" fill="white" opacity="0.22"/>
          <rect x="46" y="273" width="84" height="6" rx="3" fill="#162550"/>
          <!-- Head -->
          <circle cx="83" cy="214" r="18" fill="#FFCC99"/>
          <!-- Hair -->
          <ellipse cx="83" cy="199" rx="16" ry="10" fill="#2C1A0A"/>
          <rect x="67" y="197" width="32" height="12" rx="2" fill="#2C1A0A"/>
          <!-- Eyes -->
          <circle cx="78" cy="216" r="2.2" fill="#7A4A1A"/>
          <circle cx="88" cy="216" r="2.2" fill="#7A4A1A"/>
          <!-- Smile -->
          <path d="M79 223 Q83 226 87 223" fill="none" stroke="#C67A4A" stroke-width="1.5" stroke-linecap="round"/>
          <!-- Neck -->
          <rect x="76" y="231" width="14" height="9" rx="3.5" fill="#FFCC99"/>
          <!-- Shirt (brand orange) -->
          <rect x="63" y="239" width="40" height="33" rx="9" fill="#FD4401"/>
          <!-- Collar -->
          <path d="M76 240 L83 249 L90 240" fill="none" stroke="white" stroke-width="1.5" opacity="0.4"/>
          <!-- Left arm extended -->
          <path d="M63 247 Q44 255 51 269" fill="none" stroke="#FFCC99" stroke-width="8.5" stroke-linecap="round"/>
          <circle cx="51" cy="269" r="5.5" fill="#FFCC99"/>
          <!-- Right arm extended -->
          <path d="M103 247 Q120 255 115 269" fill="none" stroke="#FFCC99" stroke-width="8.5" stroke-linecap="round"/>
          <circle cx="115" cy="269" r="5.5" fill="#FFCC99"/>

          <!-- ===== PERSON 2: Standing, pointing (right) ===== -->
          <!-- Head -->
          <circle cx="435" cy="132" r="18" fill="#FFDDB4"/>
          <!-- Hair -->
          <ellipse cx="435" cy="117" rx="16" ry="9" fill="#1A1100"/>
          <rect x="419" y="117" width="32" height="10" rx="2" fill="#1A1100"/>
          <!-- Eyes -->
          <circle cx="430" cy="134" r="2.2" fill="#8A5A20"/>
          <circle cx="440" cy="134" r="2.2" fill="#8A5A20"/>
          <!-- Smile -->
          <path d="M431 141 Q435 144 439 141" fill="none" stroke="#CC8844" stroke-width="1.5" stroke-linecap="round"/>
          <!-- Neck -->
          <rect x="428" y="149" width="14" height="9" rx="3.5" fill="#FFDDB4"/>
          <!-- Shirt (blue) -->
          <rect x="419" y="158" width="32" height="42" rx="8" fill="#4A9EFF"/>
          <!-- Collar -->
          <path d="M428 159 L435 168 L442 159" fill="none" stroke="white" stroke-width="1.5" opacity="0.4"/>
          <!-- Left arm (pointing to monitor) -->
          <path d="M419 165 Q388 174 362 192" fill="none" stroke="#FFDDB4" stroke-width="8.5" stroke-linecap="round"/>
          <circle cx="362" cy="192" r="6" fill="#FFDDB4"/>
          <!-- Right arm -->
          <path d="M451 165 Q462 178 460 198" fill="none" stroke="#FFDDB4" stroke-width="8.5" stroke-linecap="round"/>
          <circle cx="460" cy="198" r="5.5" fill="#FFDDB4"/>
          <!-- Legs -->
          <rect x="421" y="199" width="13" height="40" rx="6.5" fill="#1E3060"/>
          <rect x="438" y="199" width="13" height="40" rx="6.5" fill="#1E3060"/>
          <!-- Feet -->
          <ellipse cx="427" cy="237" rx="10" ry="5.5" fill="#0D1B35"/>
          <ellipse cx="444" cy="237" rx="10" ry="5.5" fill="#0D1B35"/>

          <!-- ===== CONNECTING LINES ===== -->
          <line x1="122" y1="254" x2="152" y2="215" stroke="#4A9EFF" stroke-width="1.2" stroke-dasharray="5,4" opacity="0.3"/>
          <line x1="115" y1="68" x2="152" y2="96" stroke="#FD4401" stroke-width="1" stroke-dasharray="4,3" opacity="0.28"/>
          <line x1="386" y1="68" x2="350" y2="96" stroke="#FEE500" stroke-width="1" stroke-dasharray="4,3" opacity="0.28"/>
          <line x1="406" y1="255" x2="370" y2="235" stroke="#4A9EFF" stroke-width="1" stroke-dasharray="4,3" opacity="0.25"/>

          <!-- ===== PARTICLES ===== -->
          <circle cx="162" cy="58" r="3.5" fill="#FD4401" opacity="0.7"/>
          <circle cx="173" cy="47" r="2" fill="#FD4401" opacity="0.38"/>
          <circle cx="352" cy="46" r="3" fill="#4A9EFF" opacity="0.7"/>
          <circle cx="363" cy="56" r="1.8" fill="#4A9EFF" opacity="0.38"/>
          <circle cx="32" cy="170" r="2.5" fill="#FEE500" opacity="0.5"/>
          <circle cx="466" cy="260" r="3" fill="#4AFF91" opacity="0.5"/>
          <circle cx="477" cy="272" r="1.8" fill="#4AFF91" opacity="0.3"/>
          <circle cx="112" cy="308" r="2.5" fill="#FD4401" opacity="0.4"/>
          <circle cx="208" cy="30" r="2" fill="white" opacity="0.28"/>
          <circle cx="292" cy="36" r="2" fill="white" opacity="0.28"/>
          <circle cx="486" cy="98" r="2.5" fill="#4A9EFF" opacity="0.38"/>
          <circle cx="18" cy="238" r="2" fill="#FEE500" opacity="0.35"/>

          <!-- ===== SE팀 BADGE ===== -->
          <g transform="translate(188,318)">
            <rect width="124" height="33" rx="16.5" fill="#FD4401"/>
            <rect x="1.5" y="1.5" width="121" height="30" rx="15" fill="none" stroke="#FEE500" stroke-width="1.2" opacity="0.55"/>
            <text x="62" y="22" font-family="'Space Grotesk','Noto Sans KR',sans-serif" font-size="12" font-weight="800" fill="white" text-anchor="middle" letter-spacing="0.4">SE팀 · 주간보고</text>
          </g>
        </svg>
      </div>

      <!-- Tagline -->
      <div class="illus-tagline">
        <h2>SE팀 스마트 업무 관리</h2>
        <p>주간 보고를 효율적으로 작성하고<br>팀의 업무 현황을 한눈에 파악하세요.</p>
      </div>
    </div>

    <!-- ============ RIGHT: Login Form Panel ============ -->
    <div class="form-panel">
      <!-- Mobile only brand -->
      <div class="mobile-logo">
        <div class="mobile-brand-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6"/>
          </svg>
        </div>
        <span>주간업무보고</span>
      </div>

      <!-- Form header -->
      <div class="form-header">
        <h1>로그인</h1>
        <p>업무 보고 시스템에 로그인하세요</p>
      </div>

      <!-- Success flash -->
      <div v-if="$page.props.flash?.success" class="success-msg">
        ✓ {{ $page.props.flash.success }}
      </div>

      <!-- Login form card -->
      <div class="form-card">
        <form @submit.prevent="submit">
          <div class="field">
            <label>아이디</label>
            <input v-model="form.username" type="text" autocomplete="username"
              placeholder="아이디를 입력하세요" autofocus
              :class="{ 'field-error': form.errors.username }"/>
            <p v-if="form.errors.username" class="error-text">{{ form.errors.username }}</p>
          </div>
          <div class="field">
            <label>비밀번호</label>
            <input v-model="form.password" type="password" autocomplete="current-password"
              placeholder="비밀번호를 입력하세요"
              :class="{ 'field-error': form.errors.password }"/>
            <p v-if="form.errors.password" class="error-text">{{ form.errors.password }}</p>
          </div>
          <button type="submit" :disabled="form.processing" class="btn-login">
            {{ form.processing ? '로그인 중...' : '로그인' }}
          </button>
        </form>
      </div>

      <!-- Divider -->
      <div class="divider"><span>또는</span></div>

      <!-- Kakao -->
      <a href="/auth/kakao?intent=login" class="btn-kakao">
        <svg width="18" height="18" viewBox="0 0 512 512" fill="#1A1100">
          <path d="M255.5 48C141.1 48 48 126.1 48 222.3c0 64.3 40.5 120.8 101.3 153.2l-21.7 80.6c-1.9 7.2 5.8 13.1 12.2 9.1L233.8 401c7.1.8 14.4 1.2 21.7 1.2 114.4 0 207.5-78.1 207.5-174.3S369.9 48 255.5 48z"/>
        </svg>
        카카오 로그인
      </a>
      <p class="kakao-note">처음이라면 자동으로 가입 신청됩니다 (관리자 승인 필요)</p>

      <!-- Register link -->
      <div class="register-link">
        <span>계정이 없으신가요?</span>
        <Link href="/register">회원가입 신청</Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: null })

const form = useForm({
  username: '',
  password: '',
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<style scoped>
/* ====== PAGE LAYOUT ====== */
.login-page {
  min-height: 100vh;
  display: flex;
  background: #FFF8EE;
}

/* ====== LEFT PANEL ====== */
.illus-panel {
  width: 56%;
  background: linear-gradient(140deg, #0D1B35 0%, #152A48 100%);
  display: flex;
  flex-direction: column;
  padding: 30px 40px 30px 40px;
  position: relative;
  overflow: hidden;
}

.brand-mark {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}
.brand-icon {
  width: 36px; height: 36px;
  background: #FD4401;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 0 18px rgba(253,68,1,0.45);
}
.brand-mark > span {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 15px; font-weight: 800;
  color: white; letter-spacing: -0.02em;
}

.illus-inner {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px 0;
  min-height: 0;
}
.illus-svg {
  width: 100%;
  max-width: 460px;
  height: auto;
}

.illus-tagline {
  flex-shrink: 0;
  padding-top: 12px;
}
.illus-tagline h2 {
  font-family: 'Space Grotesk', 'Noto Sans KR', sans-serif;
  font-size: 20px; font-weight: 800;
  color: white; margin: 0 0 8px;
}
.illus-tagline p {
  font-size: 13px;
  color: rgba(255,255,255,0.5);
  line-height: 1.65; margin: 0;
}

/* ====== RIGHT PANEL ====== */
.form-panel {
  width: 44%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 28px;
  overflow-y: auto;
}

.mobile-logo { display: none; }

.form-header {
  text-align: center;
  margin-bottom: 22px;
  width: 100%; max-width: 360px;
}
.form-header h1 {
  font-family: 'Space Grotesk', 'Noto Sans KR', sans-serif;
  font-size: 26px; font-weight: 800;
  color: #1A1100; margin: 0 0 5px;
}
.form-header p {
  font-size: 13px; color: #9A8F7A; margin: 0;
}

.success-msg {
  background: #DCFCE7;
  border: 2px solid #16A34A;
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  font-size: 13px; color: #16A34A;
  font-weight: 600; text-align: center;
  width: 100%; max-width: 360px;
}

.form-card {
  background: white;
  border: 2px solid #1A1100;
  border-radius: 16px;
  box-shadow: 4px 4px 0 #1A1100;
  padding: 26px;
  width: 100%; max-width: 360px;
}
.form-card form { display: flex; flex-direction: column; gap: 16px; }

.field { display: flex; flex-direction: column; gap: 5px; }
.field label {
  font-size: 11px; font-weight: 700; color: #9A8F7A;
}
.field input {
  border: 2px solid #E8E0D0;
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 14px; color: #1A1100;
  background: #FFFBF5;
  outline: none;
  transition: border-color 0.15s, background 0.15s;
  font-family: 'Noto Sans KR', sans-serif;
}
.field input:focus { border-color: #FD4401; background: white; }
.field input.field-error { border-color: #FD4401; }
.field input::placeholder { color: #C8BFA8; }
.error-text { font-size: 11px; color: #FD4401; margin: 0; }

.btn-login {
  width: 100%;
  padding: 13px;
  background: #FD4401;
  color: white;
  border: 2px solid #1A1100;
  border-radius: 12px;
  box-shadow: 3px 3px 0 #1A1100;
  font-family: 'Space Grotesk', 'Noto Sans KR', sans-serif;
  font-size: 14px; font-weight: 800;
  cursor: pointer;
  transition: transform 0.1s, box-shadow 0.1s;
  margin-top: 4px;
}
.btn-login:hover:not(:disabled) { transform: translate(-1px,-1px); box-shadow: 4px 4px 0 #1A1100; }
.btn-login:active:not(:disabled) { transform: translate(1px,1px); box-shadow: 2px 2px 0 #1A1100; }
.btn-login:disabled { opacity: 0.6; cursor: not-allowed; }

.divider {
  display: flex; align-items: center; gap: 10px;
  margin: 16px 0; width: 100%; max-width: 360px;
}
.divider::before, .divider::after {
  content: ''; flex: 1; height: 1.5px; background: #E8E0D0;
}
.divider span { font-size: 11px; color: #9A8F7A; white-space: nowrap; }

.btn-kakao {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%; max-width: 360px;
  background: #FEE500; color: #1A1100;
  border: 2px solid #1A1100;
  border-radius: 14px; padding: 13px;
  font-size: 14px; font-weight: 800;
  text-decoration: none;
  box-shadow: 3px 3px 0 #1A1100;
  font-family: 'Space Grotesk', 'Noto Sans KR', sans-serif;
  transition: transform 0.1s, box-shadow 0.1s;
}
.btn-kakao:hover { transform: translate(-1px,-1px); box-shadow: 4px 4px 0 #1A1100; }
.btn-kakao:active { transform: translate(1px,1px); box-shadow: 2px 2px 0 #1A1100; }

.kakao-note {
  font-size: 11px; color: #9A8F7A;
  text-align: center; margin: 8px 0 0;
  width: 100%; max-width: 360px;
}

.register-link { text-align: center; margin-top: 14px; }
.register-link span { font-size: 13px; color: #9A8F7A; }
.register-link a {
  font-size: 13px; font-weight: 700;
  color: #FD4401; margin-left: 6px;
  text-decoration: none;
}
.register-link a:hover { text-decoration: underline; }

/* ====== MOBILE ====== */
@media (max-width: 768px) {
  .illus-panel { display: none; }
  .form-panel { width: 100%; padding: 32px 20px; }
  .mobile-logo {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 24px; justify-content: center;
  }
  .mobile-brand-icon {
    width: 34px; height: 34px;
    background: #FD4401; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 2px 2px 0 #1A1100;
  }
  .mobile-logo > span {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 18px; font-weight: 800; color: #1A1100;
  }
}
</style>
